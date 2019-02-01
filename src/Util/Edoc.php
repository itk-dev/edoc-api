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

    public function getItemList($type, array $criteria = [])
    {
        $document = $this->buildRequestDocument(
            [
                'ObjectGroup' => $type,
                'SearchCriterias' => $criteria,
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
            $data[] = XmlHelper::xml2array($el);
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
        $document = $this->buildDocument([
            'edoc:Case' => array_merge([
                'fesd:UserIdentifier' => $this->userIdentifier,
            ], $this->addNs($data)),
        ]);

        $result = $this->invoke('CreateCaseFile', $document);

        if (!isset($result->CreateCaseFileResult)) {
            throw new EdocException('Error creating case file');
        }

        $caseFile = new CaseFile(XmlHelper::xml2array($result->CreateCaseFileResult));

        // The result of "CreateCaseFile" has very little information. (Try to) Convert it to a full CaseFile object.
        return $this->getCaseFile($caseFile->CaseFileIdentifier) ?? $caseFile;
    }

    /**
     * Update a document.
     *
     * @param CaseFile|string $case
     * @param array           $data
     *
     * @throws EdocException
     *
     * @return bool
     */
    public function updateCaseFile($case, array $data)
    {
        $identifier = $case instanceof CaseFile ? $case->CaseFileIdentifier : $case;
        $document = $this->buildDocument([
            'edoc:Case' => array_merge([
                'fesd:UserIdentifier' => $this->userIdentifier,
                'fesd:CaseFileIdentifier' => $identifier,
            ], $this->addNs($data)),
        ]);

        $result = $this->invoke('UpdateCaseFile', $document);

        if (!isset($result->UpdateCaseFileResult)) {
            throw new EdocException('Error updating document.');
        }

        return true;
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
     * Create a document.
     *
     * @param CaseFile $case
     * @param array    $data
     *
     * @throws EdocException
     *
     * @return Document
     */
    public function createDocumentAndDocumentVersion(CaseFile $case, array $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Root xmlns:fesd="'.self::NS_FESD.'" xmlns:edoc="'.self::NS_EDOC.'"/>';
        $document = XmlHelper::array2xml([
            'fesd:CaseFileIdentifier' => $case->CaseFileIdentifier,
            'edoc:Document' => array_merge([
                'fesd:UserIdentifier' => $this->userIdentifier,
            ], $this->addNs($data)),
        ], $xml);

        $result = $this->invoke('CreateDocumentAndDocumentVersion', $document);

        if (!isset($result->CreateDocumentAndDocumentVersionResult)) {
            throw new EdocException('Error creating document');
        }

        $document = new Document(XmlHelper::xml2array($result->CreateDocumentAndDocumentVersionResult));

        // For some reason, the result of "CreateDocumentAndDocumentVersionResult" does not include
        // "DocumentVersionIdentifier", so we get it by performing a search (!).
        if (null === $document->DocumentVersionIdentifier) {
            $result = $this->searchDocument(['DocumentIdentifier' => $document->DocumentIdentifier]);
            if (1 === \count($result)) {
                $document = $result[0];
            }
        }

        return $document;
    }

    public function createDocumentVersion(Document $document, array $data)
    {
        $identifier = $document->DocumentIdentifier;
        $request = $this->buildDocument([
            'edoc:DocumentVersion' => array_merge([
                'fesd:UserIdentifier' => $this->userIdentifier,
                'fesd:DocumentVersionIdentifier' => $document->DocumentVersionIdentifier,
                'edoc:FileVariantCode' => 0, // "Produktionsformat"
            ], $this->addNs($data)),
        ]);

        $result = $this->invoke('CreateDocumentVersion', $request);
        if (!isset($result->CreateDocumentVersionResult) || !empty($result->CreateDocumentVersionResult)) {
            throw new EdocException('Error creating document version');
        }

        return true;
    }

    /**
     * Update a document.
     *
     * @param Document|string $document
     * @param array           $data
     *
     * @throws EdocException
     *
     * @return bool
     */
    public function updateDocument(Document $document, array $data)
    {
        $identifier = $document->DocumentIdentifier;
        $request = $this->buildDocument([
            'edoc:Document' => array_merge([
                'fesd:UserIdentifier' => $this->userIdentifier,
                'fesd:DocumentIdentifier' => $identifier,
            ], $this->addNs($data)),
        ]);

        $result = $this->invoke('UpdateDocument', $request);
        if (!isset($result->UpdateDocumentResult)) {
            throw new EdocException('Error updating document.');
        }

        return true;
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

    public function debug()
    {
        $this->updateDocument(
            '7cfcb29f-6541-449f-b06b-ba3be2ad9871',
            [
                'Summary' => 'Test document'.date('c'),
            ]
        );

        header('Content-type: text/plain');
        echo var_export(null, true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        $document = $this->buildDocument([
            'edoc:DocumentVersion' => [
                'fesd:UserIdentifier' => $this->userIdentifier,
                'fesd:DocumentVersionIdentifier' => '0879f537-4a1d-4aef-8d14-e1c3928bb59b',
                'fesd:ArchiveFormatCode' => 13,
                'edoc:FileVariantCode' => 2,
                'fesd:DocumentContents' => base64_encode('Hep-hey!'),
                'edoc:Comment' => 'Updated at '.date('c'),
            ],
        ]);

        echo XmlHelper::format($document);

        $result = $this->invoke('CreateDocumentVersion', $document);

        echo var_export(isset($result->CreateDocumentVersionResult) && '' === $result->CreateDocumentVersionResult, true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        return isset($result->CreateDocumentVersionResult) && '' === $result->CreateDocumentVersionResult;
        echo var_export($result->CreateDocumentVersionResult, true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);
        echo XmlHelper::format(reset($result));
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        return;
        $document = $this->buildDocument([
            self::FESD.':UserIdentifier' => $this->userIdentifier,
            'fesd:UserIdentifier' => $this->userIdentifier,
        ]);

        echo XmlHelper::format($document);

        $result = $this->invoke('FileDocument', $document);

        echo XmlHelper::format(reset($result));
        die(__FILE__.':'.__LINE__.':'.__METHOD__);
    }

    /**
     * Add namespace prefix to a key.
     *
     * @param $key
     *
     * @return string
     */
    private function getNsKey($key)
    {
        // Check if key already has namespace.
        if (preg_match('/^[a-z]+:/', $key)) {
            return $key;
        }

        switch ($key) {
            case 'CaseWorkerAccountName':
            case 'DocumentVersion':
            case 'HandlingCodeId':
            case 'HasPersonrelatedInfo':
            case 'OrganisationReference':
            case 'PrimaryCode':
            case 'Project':
            case 'Summary':
                return self::EDOC.':'.$key;
            case 'ArchiveFormatCode':
            case 'CaseFileManagerReference':
            case 'CaseFileTypeCode':
            case 'DocumentContents':
            case 'Summary':
            case 'TitleText':
            case 'TitleText':
            case 'UserIdentifier':
                return self::FESD.':'.$key;
        }

        return $key;
    }

    /**
     * Add namespace (alias) to array keys.
     *
     * @param array $data
     *
     * @return array
     */
    private function addNs(array $data)
    {
        $nsData = [];
        foreach ($data as $key => $value) {
            $nsData[$this->getNsKey($key)] = \is_array($value) ? $this->addNs($value) : $value;
        }

        return $nsData;
    }

    private function construct($class, array $items)
    {
        return array_map(function (array $data) use ($class) {
            return new $class($data);
        }, $items);
    }

    private function invoke(string $method, \SimpleXMLElement $document)
    {
        $result = $this->client->{$method}([
            'XmlDocument' => $document->asXML(),
        ]);

        if (!isset($result->{$method.'Result'})) {
            throw (new EdocException('Error calling eDoc api method'.$method))
                ->setData($result);
        }

        $data = XmlHelper::xml2array($result->{$method.'Result'});
        if (isset($data['ErrorCode'])) {
            throw (new EdocException($data['ErrorCode'].': '.($data['ErrorDescriptionText'] ?? '')))
                ->setData($data);
        }

        return $result;
    }

    /**
     * Build a request document.
     *
     * @param array $data
     *
     * @return \SimpleXMLElement
     */
    private function buildDocument(array $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Root';
        $xml .= ' xmlns:'.self::EDOC.'="'.self::NS_EDOC.'"';
        $xml .= ' xmlns:'.self::FESD.'="'.self::NS_FESD.'"';
        $xml .= '/>';

        return XmlHelper::array2xml($data, $xml);
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
