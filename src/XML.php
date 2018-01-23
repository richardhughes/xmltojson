<?php

namespace Converter;

use SimpleXMLElement;

class XML
{
    private $xml;

    private $options;

    private $defaultOptions = [
        'namespaceSeparator' => ':',
        'attributePrefix' => '@',
        'alwaysArray' => [],
        'autoArray' => true,
        'textContent' => '$',
        'autoText' => true,
        'keySearch' => false,
        'keyReplace' => false,
    ];

    public function __construct(SimpleXMLElement $xml, array $options = [])
    {
        $this->xml = $xml;
        $this->options = array_merge($this->defaultOptions, $options);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        $namespaces = $this->xml->getDocNamespaces();
        $namespaces[''] = null;

        $attributesArray = [];
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->attributes($namespace) as $attributeName => $attribute) {
                if ($this->options['keySearch']) {
                    $attributeName = $this->replaceKey($attributeName);
                }
                $attributeKey = $this->options['attributePrefix']
                    . ($prefix ? $prefix . $this->options['namespaceSeparator'] : '')
                    . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }

        $tagsArray = [];
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->children($namespace) as $childXml) {
                $object = new XML($childXml, $this->options);
                $childArray = $object->toArray();
                [$childTagName, $childProperties] = each($childArray);

                if ($this->options['keySearch']) {
                    $childTagName = $this->replaceKey($childTagName);
                }

                if ($prefix) {
                    $childTagName = $prefix . $this->options['namespaceSeparator'] . $childTagName;
                }

                if (!isset($tagsArray[$childTagName])) {
                    $tagsArray[$childTagName] =
                        in_array($childTagName, $this->options['alwaysArray']) || !$this->options['autoArray']
                            ? [$childProperties] : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    $tagsArray[$childTagName] = [$tagsArray[$childTagName], $childProperties];
                }
            }
        }

        $textContentArray = [];
        $plainText = trim((string)$this->xml);
        if (!empty($plainText)) {
            $textContentArray[$this->options['textContent']] = $plainText;
        }

        $propertiesArray = $plainText;
        if(!$this->options['autoText'] ||
            $attributesArray ||
            $tagsArray ||
            empty($plainText)){
            $propertiesArray = array_merge($attributesArray, $tagsArray, $textContentArray);
        }

        return [
            $this->xml->getName() => $propertiesArray
        ];
    }

    private function replaceKey($attributeName): string
    {
        return str_replace(
            $this->options['keySearch'],
            $this->options['keyReplace'],
            $attributeName
        );
    }
}