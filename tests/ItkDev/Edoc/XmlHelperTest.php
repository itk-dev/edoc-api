<?php

namespace ItkDev\Edoc;

use PHPUnit\Framework\TestCase;

class XmlHelperTest extends TestCase
{
    /**
     * @dataProvider array2xmlProvider
     */
    public function testArray2xml(array $data, string $rootName, $namespaces, string $expected, string $message = null)
    {
        $actual = XmlHelper::array2xml($data, $rootName, $namespaces);
        $this->assertXmlStringEqualsXmlString($expected, $actual->asXML(), $message);
    }

    /**
     * @dataProvider xml2arrayProvider
     */
    public function testXml2array(string $xml, $keepNamespacePrefix, array $expected, string $message = null)
    {
        $sxe = new \SimpleXMLElement($xml);
        $actual = XmlHelper::xml2array($sxe, $keepNamespacePrefix);
        $this->assertEquals($expected, $actual, $message);
    }

    public function array2xmlProvider()
    {
        return [
            [[], 'root', null, '<root/>'],
            [
                [
                    'CaseFilesSearchResult' => [
                        'CaseFileSearchResult' => [
                            'CaseFileIdentifier' => '200001',
                            'Summary' => 'Her sagsresume',
                            'TitleText' => 'Sag 1',
                            'Links' => [
                                'OpenCaseFile' => 'http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&module=Case&subtype=2&recno=200001',
                            ],
                        ],
                    ],
                ],
                'Root',
                null,
                '<Root>
  <CaseFilesSearchResult>
    <CaseFileSearchResult>
      <CaseFileIdentifier>200001</CaseFileIdentifier>
      <Summary>Her sagsresume</Summary>
      <TitleText>Sag 1</TitleText>
      <Links>
        <OpenCaseFile>http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&amp;module=Case&amp;subtype=2&amp;recno=200001</OpenCaseFile>
      </Links>
    </CaseFileSearchResult>
  </CaseFilesSearchResult>
</Root>',
            ],
            [
                [
                    'CaseFileSearch' => [
                        'UserIdentifier' => 'www.fujitsu.dk/esdh/bruger/xyz',
                        'SearchCriterias' => [
                            'TitleText' => 'Sag 1',
                        ],
                        'ResultFields' => [
                            'CaseSerialNumber',
                            'Summary',
                            'TimeStamp',
                            'TitleText',
                        ],
                    ],
                ],
                'Root',
                [
                    'edoc' => 'http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/',
                ],
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/">
	<edoc:CaseFileSearch>
		<edoc:UserIdentifier>www.fujitsu.dk/esdh/bruger/xyz</edoc:UserIdentifier>
		<edoc:SearchCriterias>
			<edoc:TitleText>Sag 1</edoc:TitleText>
		</edoc:SearchCriterias>
		<edoc:ResultFields>
			<edoc:CaseSerialNumber></edoc:CaseSerialNumber>
			<edoc:Summary></edoc:Summary>
			<edoc:TimeStamp></edoc:TimeStamp>
			<edoc:TitleText></edoc:TitleText>
		</edoc:ResultFields>
	</edoc:CaseFileSearch>
</Root>',
            ],
            [
                [
                    'edoc:CaseFileSearch' => [
                        'edoc:UserIdentifier' => 'www.fujitsu.dk/esdh/bruger/xyz',
                        'edoc:SearchCriterias' => [
                            'edoc:TitleText' => 'Sag 1',
                        ],
                        'edoc:ResultFields' => [
                            'edoc:CaseSerialNumber',
                            'edoc:Summary',
                            'edoc:TimeStamp',
                            'edoc:TitleText',
                        ],
                    ],
                ],
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/"/>',
                null,
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/">
	<edoc:CaseFileSearch>
		<edoc:UserIdentifier>www.fujitsu.dk/esdh/bruger/xyz</edoc:UserIdentifier>
		<edoc:SearchCriterias>
			<edoc:TitleText>Sag 1</edoc:TitleText>
		</edoc:SearchCriterias>
		<edoc:ResultFields>
			<edoc:CaseSerialNumber></edoc:CaseSerialNumber>
			<edoc:Summary></edoc:Summary>
			<edoc:TimeStamp></edoc:TimeStamp>
			<edoc:TitleText></edoc:TitleText>
		</edoc:ResultFields>
	</edoc:CaseFileSearch>
</Root>',
            ],
            [
                [
                    'edoc:CaseFilesSearchResult' => [
                        'edoc:CaseFileSearchResult' => [
                            'fesd:CaseFileIdentifier' => '200001',
                            'edoc:Summary' => 'Her sagsresume',
                            'fesd:TitleText' => 'Sag 1',
                            'edoc:Links' => [
                                'edoc:OpenCaseFile' => 'http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&module=Case&subtype=2&recno=200001',
                            ],
                        ],
                    ],
                ],
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" xmlns:fesd="http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/"/>',
                null,
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" xmlns:fesd="http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/">
  <edoc:CaseFilesSearchResult>
    <edoc:CaseFileSearchResult>
      <fesd:CaseFileIdentifier>200001</fesd:CaseFileIdentifier>
      <edoc:Summary>Her sagsresume</edoc:Summary>
      <fesd:TitleText>Sag 1</fesd:TitleText>
      <edoc:Links>
        <edoc:OpenCaseFile>http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&amp;module=Case&amp;subtype=2&amp;recno=200001</edoc:OpenCaseFile>
      </edoc:Links>
    </edoc:CaseFileSearchResult>
  </edoc:CaseFilesSearchResult>
</Root>',
            ],
        ];
    }

    public function xml2arrayProvider()
    {
        return [
            [
                '<root/>',
                false,
                [],
                'Empty document',
            ],
            [
                '<root xmlns:x="http://www.w3.org/1999/xhtml"><x:e/></root>',
                false,
                ['e' => null],
            ],
            [
                '<e><item /><item /></e>',
                false,
                ['item' => [null, null]],
                'List'
            ],
            [
                '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" xmlns:fesd="http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/">
  <edoc:CaseFilesSearchResult>
    <edoc:CaseFileSearchResult>
      <fesd:CaseFileIdentifier>200001</fesd:CaseFileIdentifier>
      <edoc:Summary>Her sagsresume</edoc:Summary>
      <fesd:TitleText>Sag 1</fesd:TitleText>
      <edoc:Links>
        <edoc:OpenCaseFile>http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&amp;module=Case&amp;subtype=2&amp;recno=200001</edoc:OpenCaseFile>
      </edoc:Links>
    </edoc:CaseFileSearchResult>
  </edoc:CaseFilesSearchResult>
</Root>',
                false,
                [
                    'CaseFilesSearchResult' => [
                        'CaseFileSearchResult' => [
                            'CaseFileIdentifier' => '200001',
                            'Summary' => 'Her sagsresume',
                            'TitleText' => 'Sag 1',
                            'Links' => [
                                'OpenCaseFile' => 'http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&module=Case&subtype=2&recno=200001',
                            ],
                        ],
                    ],
                ],
            ],
            [
            '<Root xmlns:edoc="http://www.fujitsu.dk/esdh/xml/schemas/2007/01/05/" xmlns:fesd="http://rep.oio.dk/fesd.dk/xml/schemas/2005/04/20/">
  <edoc:CaseFilesSearchResult>
    <edoc:CaseFileSearchResult>
      <fesd:CaseFileIdentifier>200001</fesd:CaseFileIdentifier>
      <edoc:Summary>Her sagsresume</edoc:Summary>
      <fesd:TitleText>Sag 1</fesd:TitleText>
      <edoc:Links>
        <edoc:OpenCaseFile>http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&amp;module=Case&amp;subtype=2&amp;recno=200001</edoc:OpenCaseFile>
      </edoc:Links>
    </edoc:CaseFileSearchResult>
  </edoc:CaseFilesSearchResult>
</Root>',
            true,
                [
                    'edoc:CaseFilesSearchResult' => [
                        'edoc:CaseFileSearchResult' => [
                            'fesd:CaseFileIdentifier' => '200001',
                            'edoc:Summary' => 'Her sagsresume',
                            'fesd:TitleText' => 'Sag 1',
                            'edoc:Links' => [
                                'edoc:OpenCaseFile' => 'http://vm-demo:8080/sites/1030/locator.aspx?name=DMS.Case.Details.2&module=Case&subtype=2&recno=200001',
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
