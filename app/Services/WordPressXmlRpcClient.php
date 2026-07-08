<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class WordPressXmlRpcClient
{
    public static function call(Site $site, string $method, array $params = []): mixed
    {
        $response = Http::timeout(30)
            ->withBody(self::buildRequest($method, $params), 'text/xml')
            ->post("{$site->url}/xmlrpc.php");

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}");
        }

        $useInternalErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($xml === false) {
            throw new RuntimeException('Invalid XML-RPC response (non-XML body received)');
        }

        if (isset($xml->fault)) {
            $fault = self::decodeValue($xml->fault->value);
            throw new RuntimeException($fault['faultString'] ?? 'XML-RPC call failed');
        }

        if (!isset($xml->params->param->value)) {
            throw new RuntimeException('Invalid XML-RPC response (missing params)');
        }

        return self::decodeValue($xml->params->param->value);
    }

    private static function buildRequest(string $method, array $params): string
    {
        $encoded = implode('', array_map(
            fn ($param) => '<param>' . self::encodeValue($param) . '</param>',
            $params
        ));

        return <<<XML
        <?xml version="1.0"?>
        <methodCall>
            <methodName>{$method}</methodName>
            <params>{$encoded}</params>
        </methodCall>
        XML;
    }

    private static function encodeValue(mixed $value): string
    {
        return match (true) {
            $value instanceof XmlRpcBase64Value => '<value><base64>' . base64_encode($value->raw) . '</base64></value>',
            is_int($value) => "<value><int>{$value}</int></value>",
            is_bool($value) => '<value><boolean>' . ($value ? 1 : 0) . '</boolean></value>',
            is_array($value) && array_is_list($value) => '<value><array><data>'
                . implode('', array_map(fn ($v) => self::encodeValue($v), $value))
                . '</data></array></value>',
            is_array($value) => '<value><struct>'
                . implode('', array_map(
                    fn ($k, $v) => '<member><name>' . htmlspecialchars((string) $k, ENT_XML1) . '</name>' . self::encodeValue($v) . '</member>',
                    array_keys($value),
                    $value
                ))
                . '</struct></value>',
            default => '<value><string>' . htmlspecialchars((string) $value, ENT_XML1) . '</string></value>',
        };
    }

    private static function decodeValue(SimpleXMLElement $value): mixed
    {
        $children = $value->children();

        if (count($children) === 0) {
            return (string) $value;
        }

        $node = $children[0];

        return match ($node->getName()) {
            'int', 'i4' => (int) $node,
            'double' => (float) $node,
            'boolean' => ((string) $node) === '1',
            'base64' => base64_decode((string) $node),
            'struct' => self::decodeStruct($node),
            'array' => self::decodeArray($node),
            default => (string) $node,
        };
    }

    private static function decodeStruct(SimpleXMLElement $struct): array
    {
        $result = [];

        foreach ($struct->member as $member) {
            $result[(string) $member->name] = self::decodeValue($member->value);
        }

        return $result;
    }

    private static function decodeArray(SimpleXMLElement $array): array
    {
        $result = [];

        foreach ($array->data->value as $value) {
            $result[] = self::decodeValue($value);
        }

        return $result;
    }
}