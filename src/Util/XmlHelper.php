<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Util;

class XmlHelper
{
    public static function format($xml)
    {
        if ($xml instanceof \SimpleXMLElement) {
            $xml = $xml->asXML();
        }
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        return $dom->saveXML();
    }

    /**
     * @param \SimpleXMLElement|string $xml
     * @param mixed                    $keepNamespacePrefixes
     *
     * @return array
     */
    public static function xml2array($xml, $keepNamespacePrefixes = false)
    {
        if (\is_string($xml)) {
            $xml = new \SimpleXMLElement($xml);
        }
        $data = [];
        $namespaces = ['' => null] + $xml->getNamespaces(true);
        foreach ($namespaces as $prefix => $url) {
            foreach ($xml->children($url) as $value) {
                $name = $value->getName();
                if ($keepNamespacePrefixes) {
                    $ns = $value->getNamespaces();
                    if (\count($ns) > 0) {
                        $prefix = array_keys($ns)[0];
                        $name = $prefix.':'.$name;
                    }
                }
                // Recurse if $value is an xml element.
                $value = ($value->count() > 0) ? self::xml2array($value, $keepNamespacePrefixes) : (string) $value;
                // Handle list (sequential array).
                if (isset($data[$name])) {
                    if (!\is_array($data[$name])) {
                        $data[$name] = [$data[$name]];
                    }
                    $data[$name][] = $value;
                } else {
                    $data[$name] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Convert array to xml.
     *
     * @param array  $data
     * @param string $root       Root element name or xml markup
     * @param null   $namespaces
     *
     * @return \SimpleXMLElement
     */
    public static function array2xml(array $data, string $root, $namespaces = null)
    {
        if (\is_string($namespaces)) {
            $namespaces = ['ns' => $namespaces];
        }
        if (0 === strpos($root, '<')) {
            $xml = $root;
        } else {
            $xml = '<'.$root;
            if (\is_array($namespaces)) {
                foreach ($namespaces as $prefix => $uri) {
                    $xml .= ' xmlns:'.$prefix.'="'.$uri.'"';
                }
            }
            $xml .= '/>';
        }

        $sxe = new \SimpleXMLElement($xml);
        self::buildXml($sxe, $data, $sxe->getDocNamespaces());

        return $sxe;
    }

    private static function buildXml(\SimpleXMLElement $sxe, array $data, array $namespaces = null, string $currentNamespace = null)
    {
        $defaultNamespacePrefix = (\count($namespaces) > 0) ? array_keys($namespaces)[0] : null;
        foreach ($data as $name => $value) {
            list($prefix, $name) = self::getPrefixAndName($name, $defaultNamespacePrefix);
            $namespace = isset($namespaces[$prefix]) ? $namespaces[$prefix] : null;
            if (\is_array($value)) {
                if (self::isSequential($value)) {
                    $value = array_combine($value, array_fill(0, \count($value), null));
                }
                $child = $sxe->addChild($name, null, $namespace);
                self::buildXml($child, $value, $namespaces, $namespace);
            } else {
                $sxe->addChild($name, htmlspecialchars($value), $namespace);
            }
        }
    }

    private static function getPrefixAndName(string $name, string $defaultPrefix = null)
    {
        return false !== strpos($name, ':') ? explode(':', $name, 2) : [$defaultPrefix, $name];
    }

    private static function isSequential(array $arr)
    {
        return $arr === [] || array_keys($arr) === range(0, \count($arr) - 1);
    }
}
