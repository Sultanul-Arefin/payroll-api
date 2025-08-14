<?php

namespace App\Helpers;

class XmlValidator
{
    public static function validate(string $xmlContent, string $xsdPath): array
    {
        libxml_use_internal_errors(true);
        $xml = new \DOMDocument();
        $xml->loadXML($xmlContent);

        if ($xml->schemaValidate($xsdPath)) {
            return ['status' => true, 'errors' => []];
        } else {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $errorMessages = array_map(function ($error) {
                return trim($error->message);
            }, $errors);

            return ['status' => false, 'errors' => $errorMessages];
        }
    }
}