<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Util;

use ItkDev\Edoc\Entity\ArchiveFormat;
use ItkDev\Edoc\Entity\CaseFile;
use ItkDev\Edoc\Entity\Document;

class Edoc
{
    const documentType = 'DocumentType';
    const _documentType = 'ArchiveFormat';

    const EDOC = 'edoc';
    const EDOCLIST = 'edoclist';
    const FESD = 'fesd';
    const NS = [
        // self::EDOC => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/14/',
        self::EDOC => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/',
        self::EDOCLIST => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/14/',
        self::FESD => 'http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/',
    ];
    const NS_EDOC = self::NS[self::EDOC];
    const NS_EDOCLIST = self::NS[self::EDOCLIST];
    const NS_FESD = self::NS[self::FESD];

    /**
     * @var EdocClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $userIdentifier;

    public function __construct(EdocClient $client, string $userIdentifier)
    {
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
    }

    public function getProjects()
    {
        return $this->getItemList(ItemListType::PROJECT);
    }

    public function getArchiveFormats()
    {
        $result = $this->getItemList(ItemListType::ARCHIVE_FORMAT);

        return $this->construct(ArchiveFormat::class, $result);
    }

    public function getAttachments(array $criteria)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Root xmlns:edoc="'.self::NS_EDOC.'"/>';
        $document = XmlHelper::array2xml([
            'UserIdentifier' => $this->userIdentifier,
            'SearchCriterias' => $criteria,
        ], $xml);

        $result = $this->invoke('GetDocumentAttachmentList', $document);

        if (!isset($result->GetDocumentAttachmentListResult)) {
            throw new EdocException('Error getting attachments');
        }

        throw new \Exception(__METHOD__.' not implemented');
    }

    public function getItemList($type)
    {
        $document = $this->buildRequestDocument(
            [
                'ObjectGroup' => $type,
            ],
            [
                'ns' => [
                    self::EDOC => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/14/',
                ],
            ]
        );

        $result = $this->invoke('GetItemList', $document);

        if (!isset($result->GetItemListResult)) {
            return null;
        }

        $document = new \SimpleXmlElement($result->GetItemListResult, 0, false, self::NS_EDOCLIST);
        $data = [];
        foreach ($document->xpath('/Root/edoclist:*/edoclist:*') as $el) {
            $pattern = '/^'.\preg_quote($type, '/').'/';
            $item = [];
            foreach ($el->children(self::EDOCLIST, true) as $value) {
                $item[preg_replace($pattern, '', $value->getName())] = (string) $value;
            }
            $data[] = $item;
        }

        return $data;
    }

    public function getDocumentList(CaseFile $case)
    {
        $document = $this->buildRequestDocument([
            'SearchCriterias' => [
                'CaseFileIdentifier' => $case->CaseFileIdentifier,
            ],
        ]);

        $result = $this->invoke('GetDocumentList', $document);

        return $result;
    }

    public function getDocumentVersion(string $identifier)
    {
        $document = $this->buildRequestDocument([
            'DocumentVersionIdentifier' => $identifier,
        ]);

        $document = new \SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?>
<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" >
	<edoc:DocumentVersionIdentifier>200076-1</edoc:DocumentVersionIdentifier>
	<edoc:UserIdentifier>www.fujitsu.dk/abc</edoc:UserIdentifier>
</Root>
');

        $result = $this->invoke('GetDocumentVersion', $document);

        return $result;
    }

    /**
     * Create a document.
     *
     * @param CaseFile $case
     * @param array    $data
     *
     * @throws EdocException
     *
     * @return Document
     */
    public function createDocument(CaseFile $case, array $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Root xmlns:fesd="'.self::NS_FESD.'" xmlns:edoc="'.self::NS_EDOC.'"/>';
        $document = XmlHelper::array2xml([
            'fesd:CaseFileIdentifier' => $case->CaseFileIdentifier,
            'edoc:Document' => [
                'fesd:UserIdentifier' => $this->userIdentifier,
                // 'fesd:DocumentTypeReference>118</fesd:DocumentTypeReference>
                'fesd:TitleText' => $data['TitleText'],
                'fesd:DocumentTypeReference' => $data['DocumentTypeReference'],
                // 'fesd:ToDocumentCategory' => 87,
                // 'fesd:DocumentDate>2009-11-01</fesd:DocumentDate>
                // 'fesd:LoanDate>2009-11-03</fesd:LoanDate>
                // 'fesd:CaseManagerReference>'.$caseManagerReference.'</fesd:CaseManagerReference>
                // 'edoc:OrganisationReference>'.$organisationReference.'</edoc:OrganisationReference>
                // 'edoc:Summary>Dette dokument er blevet oprettet via API</edoc:Summary>
                // 'edoc:CheckCode01>0</edoc:CheckCode01>

                // getItemList DocumentStatusCode
                'fesd:DocumentStatusCode' => 12,
                'edoc:DocumentVersion' => [
                    // getItemList ArchiveFormat
                    'fesd:ArchiveFormatCode' => 13, // "text/plain"
                    'fesd:DocumentContents' => \base64_encode(uniqid(__METHOD__)),
                ],
            ],
        ], $xml);

        $result = $this->invoke('CreateDocumentAndDocumentVersion', $document);

        if (!isset($result->CreateDocumentAndDocumentVersionResult)) {
            throw new EdocException('Error creating document');
        }

        return new Document(XmlHelper::xml2array($result->CreateDocumentAndDocumentVersionResult));
    }

    /**
     * Create a case file.
     *
     * @param array $data
     *
     * @throws EdocException
     *
     * @return CaseFile
     */
    public function createCaseFile(array $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Root xmlns:fesd="'.self::NS_FESD.'" xmlns:edoc="'.self::NS_EDOC.'"/>';
        $document = XmlHelper::array2xml([
            'edoc:Case' => [
                'fesd:UserIdentifier' => $this->userIdentifier,
                'fesd:CaseFileTypeCode' => $data['CaseFileTypeCode'],
                'fesd:TitleText' => $data['TitleText'],
                'fesd:CaseFileManagerReference' => $data['CaseFileManagerReference'],
                'edoc:Project' => $data['Project'],
                'edoc:HasPersonrelatedInfo' => $data['HasPersonrelatedInfo'] ? 300001 : 300002,
                'edoc:HandlingCodeId' => $data['HandlingCodeId'],
                'edoc:PrimaryCode' => $data['PrimaryCode'],
            ],
        ], $xml);

        $result = $this->invoke('CreateCaseFile', $document);

        if (!isset($result->CreateCaseFileResult)) {
            throw new EdocException('Error creating case file');
        }

        return new CaseFile(XmlHelper::xml2array($result->CreateCaseFileResult));
    }

    /**
     * @param array      $criteria
     * @param null|array $fields
     *
     * @return CaseFile[]
     */
    public function searchCaseFile(array $criteria, array $fields = null)
    {
        $document = $this->buildRequestDocument([]);
        $crit = $document->addChild('CaseFileSearch', null, self::NS_EDOC)
            ->addChild('SearchCriterias', null, self::NS_EDOC);
        foreach ($criteria as $name => $value) {
            $crit->addChild($name, $value, self::NS_EDOC);
        }

        $result = $this->invoke('SearchCaseFile', $document);

        if (!isset($result->SearchCaseFileResult)) {
            return [];
        }

        $document = new \SimpleXmlElement($result->SearchCaseFileResult, 0, false, self::NS_EDOC);

        $data = [];
        if (isset($document->CaseFilesSearchResult->CaseFileSearchResult)) {
            foreach ($document->CaseFilesSearchResult->CaseFileSearchResult as $el) {
                $data[] = new CaseFile(XmlHelper::xml2array($el));
            }
        }

        return $data;
    }

    /**
     * @param string $identifier
     *
     * @return null|CaseFile
     */
    public function getCaseFile(string $identifier)
    {
        $caseFiles = $this->searchCaseFile([
            'CaseFileIdentifier' => $identifier,
        ]);

        return 1 === \count($caseFiles) ? reset($caseFiles) : null;
    }

    /**
     * @param array      $criteria
     * @param null|array $fields
     *
     * @return array|Document[]
     */
    public function searchDocument(array $criteria, array $fields = null)
    {
        $document = $this->buildRequestDocument([]);
        $crit = $document->addChild('DocumentSearch', null, self::NS_EDOC)
            ->addChild('SearchCriterias', null, self::NS_EDOC);
        foreach ($criteria as $name => $value) {
            $crit->addChild($name, $value, self::NS_FESD);
        }

        $result = $this->invoke('SearchDocument', $document);

        if (!isset($result->SearchDocumentResult)) {
            return null;
        }

        $document = new \SimpleXmlElement($result->SearchDocumentResult, 0, false, self::NS_EDOC);

        $data = [];
        foreach ($document->Documents->Document as $el) {
            $data[] = new Document(XmlHelper::xml2array($el));
        }

        return $data;
    }

    public function getLastRequestHeaders()
    {
        return $this->client->__getLastRequestHeaders();
    }

    public function getLastRequest()
    {
        return $this->client->__getLastRequest();
    }

    public function getLastResponseHeaders()
    {
        return $this->client->__getLastResponseHeaders();
    }

    public function getLastResponse()
    {
        return $this->client->__getLastResponse();
    }

    private function construct($class, array $items)
    {
        return array_map(function (array $data) use ($class) {
            return new $class($data);
        }, $items);
    }

    private function invoke(string $method, \SimpleXMLElement $document)
    {
        return $this->client->{$method}([
            'XmlDocument' => $document->asXML(),
        ]);
    }

    /**
     * @param array       $data
     * @param null|string $userIdentifier
     *
     * @return \SimpleXmlElement
     */
    private function buildRequestDocument(array $data, array $config = null)
    {
        $userIdentifier = isset($data['UserIdentifier']) ? $data['UserIdentifier'] : $this->userIdentifier;
        // Make sure that UserIdentifier comes first in data.
        $data = array_merge(['UserIdentifier' => $userIdentifier], $data);
        $ns = array_merge(self::NS, isset($config['ns']) ? $config['ns'] : []);

        $root = '<?xml version="1.0" encoding="utf-8"?><Root xmlns:'.self::EDOC.'="'.$ns[self::EDOC].'"/>';
        //        $document = new \SimpleXmlElement($root);
        //
        //        foreach ($data as $name => $value) {
        //            if (is_array($value)) {
        //                $child = $document->addChild($name, null, $ns[self::EDOC]);
        //                foreach ($value as $n => $v) {
        //                    $child->addChild($n, $v, self::NS_EDOC);
        //                }
        //            } else {
        //                $document->addChild($name, (string)$value, $ns[self::EDOC]);
        //            }
        //        }
        //
        //        return $document;

        return XmlHelper::array2xml($data, $root);
    }
}
