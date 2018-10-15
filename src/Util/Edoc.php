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

    private $userIdentifier;

    public function __construct(EdocClient $client, string $userIdentifier)
    {
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
    }

    public function getProjects()
    {
        header('Content-type: text/plain');
        echo var_export(null, true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);
    }

    public function getArchiveFormats()
    {
        $result = $this->getItemList(ObjectGroup::ARCHIVE_FORMAT);

        return $this->construct(ArchiveFormat::class, $result);
    }

    public function getItemList($documentType)
    {
        $document = $this->buildRequestDocument(
            [
            'ObjectGroup' => $documentType,
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
            $pattern = '/^'.\preg_quote($documentType, '/').'/';
            $item = [];
            foreach ($el->children(self::EDOCLIST, true) as $value) {
                $item[preg_replace($pattern, '', $value->getName())] = (string) $value;
            }
            $data[] = $item;
        }

        return $data;
    }

    public function getDocumentList(array $case)
    {
        $document = $this->buildRequestDocument([
            'SearchCriterias' => [
                'CaseFileIdentifier' => $case['CaseFileIdentifier'],
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

    public function createDocument(string $caseFileIdentifier, array $parameters)
    {
        $caseManagerReference = '538046'; // "Mikkel Ricky"
        $organisationReference = '500131'; // "MBK-ITK"

        $document = new \SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?>
<Root xmlns:fesd="http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/"
      xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" >
	<fesd:CaseFileIdentifier>'.$caseFileIdentifier.'</fesd:CaseFileIdentifier>
	<edoc:Document>
		<fesd:UserIdentifier>www.fujitsu.dk/esdh/bruger/xyz</fesd:UserIdentifier>
		<fesd:DocumentTypeReference>118</fesd:DocumentTypeReference>
		<fesd:TitleText>Dokument oprettet fra API</fesd:TitleText>
		<fesd:DocumentDate>2009-11-01</fesd:DocumentDate>
		<fesd:LoanDate>2009-11-03</fesd:LoanDate>
		<fesd:CaseManagerReference>'.$caseManagerReference.'</fesd:CaseManagerReference>
		<edoc:OrganisationReference>'.$organisationReference.'</edoc:OrganisationReference>
		<edoc:Summary>Dette dokument er blevet oprettet via API</edoc:Summary>
		<edoc:CheckCode01>0</edoc:CheckCode01>
		<fesd:DocumentStatusCode>12</fesd:DocumentStatusCode>
		<edoc:DocumentVersion>
			<fesd:ArchiveFormatCode>13</fesd:ArchiveFormatCode>
			<fesd:DocumentContents>U2ltcGxlIHRleHQgZG9jdW1lbnQ=</fesd:DocumentContents>
		</edoc:DocumentVersion>
	</edoc:Document>
</Root>');

        $result = $this->invoke('CreateDocumentAndDocumentVersion', $document);

        header('Content-type: text/plain');
        echo var_export([
            $this->getLastRequest(),
            $result,
        ], true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        $xml = new \SimpleXmlElement($result->CreateDocumentAndDocumentVersionResult);

        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        // @var $xml SimpleXMLElement
        $domxml->loadXML($xml->asXML());
        echo $domxml->saveXML();

        header('Content-type: text/plain');
        echo var_export($xml->asXML(), true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        return $result;
    }

    /**
     * @param array      $criteria
     * @param null|array $fields
     *
     * @return array|CaseFile[]
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
            return null;
        }

        $document = new \SimpleXmlElement($result->SearchCaseFileResult, 0, false, self::NS_EDOC);

        $data = [];
        foreach ($document->CaseFilesSearchResult->CaseFileSearchResult as $el) {
            $data[] = new CaseFile(XmlHelper::xml2array($el));
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
