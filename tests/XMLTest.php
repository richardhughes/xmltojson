<?php

namespace Tests;

use Converter\XML;
use PHPUnit\Framework\TestCase;

class XMLTest extends TestCase
{

    /**
     * @dataProvider xmlToArrayDataProvider
     *
     * @param string $xml
     * @param array $expectedOutput
     */
    public function testXMLIsFormattedAsArrayCorrectly(string $xml, array $expectedOutput): void
    {
        $xmlConverter = new XML(new \SimpleXMLElement($xml), []);
        $this->assertSame($expectedOutput, $xmlConverter->toArray());
    }

    public function xmlToArrayDataProvider(): array
    {
        return [
            'Test array is generated correctly without namespaces' => [
                'xml' => '<?xml version="1.0" encoding="UTF-8"?><root><random>Hello</random><test dep="Attribute">A test</test></root>',
                'expectedOutput' => [
                    'root' => [
                        'random' => 'Hello',
                        'test' => [
                            '@dep' => 'Attribute',
                            '$' => 'A test'
                        ]
                    ]
                ]
            ]
        ];
    }
}
