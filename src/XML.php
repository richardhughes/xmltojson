<?php

namespace Converter;

use SimpleXMLElement;

class XML
{
    private $xml;

    private $options;

    public function __construct(SimpleXMLElement $xml, array $options = [])
    {
        $this->xml = $xml;
        $this->options = $options;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        $defaults = [
            'namespaceSeparator' => ':',
            'attributePrefix' => '@',
            'alwaysArray' => [],
            'autoArray' => true,
            'textContent' => '$',
            'autoText' => true,
            'keySearch' => false,
            'keyReplace' => false,
        ];

        $options = array_merge($defaults, $this->options);
        $namespaces = $this->xml->getDocNamespaces();
        $namespaces[''] = null;

        $attributesArray = [];
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->attributes($namespace) as $attributeName => $attribute) {
                if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }

        $tagsArray = [];
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->children($namespace) as $childXml) {
                $object = new XML($childXml, $options);
                $childArray = $object->toArray();
                list($childTagName, $childProperties) = each($childArray);

                if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

                if (!isset($tagsArray[$childTagName])) {
                    $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
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
        if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        return [
            $this->xml->getName() => $propertiesArray
        ];
    }
}