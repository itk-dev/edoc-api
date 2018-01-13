<?php

namespace ItkDev\Edoc;

class EdocClient
{
    private $client;
    private $options;

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

    public function __construct($wsdlUrl, $username, $password, array $options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }
        $options['soap_version'] = SOAP_1_2;

        $options['username'] = $username;
        $options['password'] = $password;
        $options['wsdlUrl'] = $wsdlUrl;

        $this->options = $options;
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
            $pattern = '/^'.preg_quote($documentType).'/';
            $item = [];
            foreach ($el->children(self::EDOCLIST, true) as $value) {
                $item[preg_replace($pattern, '', $value->getName())] = (string)$value;
            }
            $data[] = $item;
        }

        return $data;
    }

    public function getDocumentList(string $identifier)
    {
        $document = $this->buildRequestDocument([
            'SearchCriterias' => [
                'CaseFileIdentifier' => $identifier,
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
	<fesd:CaseFileIdentifier>' . $caseFileIdentifier . '</fesd:CaseFileIdentifier>
	<edoc:Document>
		<fesd:UserIdentifier>www.fujitsu.dk/esdh/bruger/xyz</fesd:UserIdentifier>
		<fesd:DocumentTypeReference>118</fesd:DocumentTypeReference>
		<fesd:TitleText>Dokument oprettet fra API</fesd:TitleText>
		<fesd:DocumentDate>2009-11-01</fesd:DocumentDate>
		<fesd:LoanDate>2009-11-03</fesd:LoanDate>
		<fesd:CaseManagerReference>' . $caseManagerReference . '</fesd:CaseManagerReference>
		<edoc:OrganisationReference>' . $organisationReference . '</edoc:OrganisationReference>
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
        /* @var $xml SimpleXMLElement */
        $domxml->loadXML($xml->asXML());
        echo $domxml->saveXML();

        header('Content-type: text/plain');
        echo var_export($xml->asXML(), true);
        die(__FILE__.':'.__LINE__.':'.__METHOD__);

        return $result;
    }

    public function searchCaseFile(array $criteria)
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
            $data[] = XmlHelper::xml2array($el);
        }

        return $data;
    }

    public function getCaseFile(string $identifier)
    {
        $caseFiles = $this->searchCaseFile([
            'CaseFileIdentifier' => $identifier,
        ]);

        return count($caseFiles) === 1 ? $caseFiles[0] : null;
    }


    private function invoke(string $method, \SimpleXMLElement $document)
    {
        if ($this->client === null) {
            $this->client = new NTLMSoapClient($this->options['wsdlUrl'], $this->options);
            $this->client->authenticate($this->options['username'], $this->options['password']);
        }

        return $this->client->{$method}([
            'XmlDocument' => $document->asXML(),
        ]);
    }

    /**
     * @param array $data
     * @param string|null $userIdentifier
     * @return \SimpleXmlElement
     */
    private function buildRequestDocument(array $data, array $config = null)
    {
        if (!isset($data['UserIdentifier'])) {
            $data['UserIdentifier'] = 'itk-dev/esdh/bruger/test';
        }
        $ns = array_merge(self::NS, isset($config['ns']) ? $config['ns'] : []);

        $root = '<Root xmlns:'.self::EDOC.'="'.$ns[self::EDOC].'"/>';
        $document = new \SimpleXmlElement($root);

        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $child = $document->addChild($name, null, $ns[self::EDOC]);
                foreach ($value as $n => $v) {
                    $child->addChild($n, $v, self::NS_EDOC);
                }
            } else {
                $document->addChild($name, (string)$value, $ns[self::EDOC]);
            }
        }

        return $document;
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
}
