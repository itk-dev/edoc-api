<?php

namespace ItkDev\Edoc;

class EdocClient
{
    private $client;

    const EDOC = 'edoc';
    const EDOCLIST = 'edoclist';
    const NS = [
        self::EDOC => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/14/',
        self::EDOCLIST => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/14/',
    ];
    const NS_EDOC = self::NS[self::EDOC];
    const NS_EDOCLIST = self::NS[self::EDOCLIST];

    public function __construct($wsdlUrl, $username, $password, array $options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }
        $options['soap_version'] = SOAP_1_2;

        $this->client = new NTLMSoapClient($wsdlUrl, $options);
        $this->client->authenticate($username, $password);
    }

    public function getItemList($userIdentifier, $documentType)
    {
        $xml = new \SimpleXmlElement('<Root xmlns:'.self::EDOC.'="'.self::NS[self::EDOC].'"/>', 0, false, self::NS_EDOC);
        $xml->addChild('UserIdentifier', $userIdentifier, self::NS_EDOC);
        $xml->addChild('ObjectGroup', $documentType, self::NS_EDOC);

        $result = $this->client->GetItemList([
            'XmlDocument' => $xml->asXML(),
        ]);

        if (!isset($result->GetItemListResult)) {
            return null;
        }

        $xml = new \SimpleXmlElement($result->GetItemListResult, 0, false, self::NS_EDOCLIST);
        $data = [];
        foreach ($xml->DocumentTypes->DocumentType as $el) {
            $data[] = [
                'id' => (string)$el->DocumentTypeId,
                'name' => (string)$el->DocumentTypeName,
            ];
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
}
