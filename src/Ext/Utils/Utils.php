<?php

namespace Rose\Ext;

use Rose\Resources;
use Rose\Strings;
use Rose\Configuration;
use Rose\Gateway;

use Rose\Errors\ArgumentError;
use Rose\Ext\Wind;
use Rose\Text;
use Rose\Math;
use Rose\Regex;
use Rose\Expr;
use Rose\Map;
use Rose\Arry;
use Rose\JSON;

// @title Utilities
// @short Utils

/**
 * Returns all the environment variables.
 * @code (`env:get-all`)
 * @example
 * (env:get-all)
 * ; {"HOME":"/home/user","PATH":"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"}
 */
Expr::register('env:get-all', function($args) {
    return $args->has(1) ? getenv($args->get(1)) : getenv();
});

/**
 * Returns an environment variable or `null` if not found.
 * @code (`env:get` <name>)
 * @example
 * (env:get "HOME")
 * ; "/home/user"
 */
Expr::register('env:get', function($args) {
    return getenv($args->get(1));
});

/**
 * Sets an environment variable.
 * @code (`env:set` <value>)
 * @example
 * (env:set "HOME=/home/user")
 * ; true
 */
Expr::register('env:set', function($args) {
    return putenv($args->get(1));
});


/**
 * Encodes a value to base64.
 * @code (`base64:encode` <value>)
 * @example
 * (base64:encode "Hello, World!")
 * ; "SGVsbG8sIFdvcmxkIQ=="
 */
Expr::register('base64:encode', function($args) {
    return base64_encode ($args->get(1));
});

/**
 * Decodes a base64 value.
 * @code (`base64:decode` <value>)
 * @example
 * (base64:decode "SGVsbG8sIFdvcmxkIQ==")
 * ; "Hello, World!"
 */
Expr::register('base64:decode', function($args) {
    return base64_decode ($args->get(1));
});

/**
 * Encodes a value to base64 URL-safe format, that is a base64 string with `+` as `-`, `/` as `_` and without any `=`.
 * @code (`base64u:encode` <value>)
 * @example
 * (base64u:encode "Hello, World!")
 * ; "SGVsbG8sIFdvcmxkIQ"
 */
Expr::register('base64u:encode', function($args) {
    return rtrim(strtr(base64_encode($args->get(1)), '+/', '-_'), '=');
});

/**
 * Decodes a base64 URL-safe value.
 * @code (`base64u:decode` <value>)
 * @example
 * (base64u:decode "SGVsbG8sIFdvcmxkIQ")
 * ; "Hello, World!"
 */
Expr::register('base64u:decode', function($args) {
    $data = $args->get(1);
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
});

/**
 * Encodes a string into hexadecimal format.
 * @code (`hex:encode` <string>)
 * @example
 * (hex:encode "Hi!")
 * ; "486921"
 */
Expr::register('hex:encode', function($args) {
    return bin2hex ($args->get(1));
});

/**
 * Decodes a hexadecimal string.
 * @code (`hex:decode` <string>)
 * @example
 * (hex:decode "486921")
 * ; "Hi!"
 */
Expr::register('hex:decode', function($args) {
    return hex2bin ($args->get(1));
});

/**
 * Encodes a value to be used in a URL.
 * @code (`url:encode` <value>)
 * @example
 * (url:encode "Hello = World")
 * ; "Hello%20%3D%20World"
 */
Expr::register('url:encode', function($args) {
    return urlencode ($args->get(1) ?? '');
});

/**
 * Decodes a URL-encoded value.
 * @code (`url:decode` <value>)
 * @example
 * (url:decode "Hello%20%3D%20World")
 * ; "Hello = World"
 */
Expr::register('url:decode', function($args) {
    return urldecode ($args->get(1) ?? '');
});

/**
 * Converts a map to a URL query string.
 * @code (`url-query:str` <fields>)
 * @example
 * (url-query:str (& name "John" "age" 35))
 * ; "name=John&age=35"
 */
Expr::register('url-query:str', function($args) {
    return $args->get(1)->map(function($value, $key) { return urlencode($key).'='.urlencode($value); })->values()->join('&');
});

/**
 * Encodes a value to be used in HTML text.
 * @code (`html-text:encode` <value>)
 * @example
 * (html-text:encode "<Hello>")
 * ; "&lt;Hello&gt;"
 */
Expr::register('html-text:encode', function($args) {
    return htmlspecialchars ($args->get(1));
});

/**
 * Decodes a value encoded for HTML text.
 * @code (`html-text:decode` <value>)
 * @example
 * (html-text:decode "&lt;Hello&gt;")
 * ; "<Hello>"
 */
Expr::register('html-text:decode', function($args) {
    return htmlspecialchars_decode ($args->get(1));
});

/**
 * Compresses a string using the Gzip algorithm.
 * @code (`gz:compress` <string>)
 * @example
 * (gz:compress "Hi!")
 * ; (binary data)
 */
Expr::register('gz:compress', function($args) {
    return gzcompress ($args->get(1));
});

/**
 * Decompresses a string compressed using the Gzip algorithm.
 * @code (`gz:decompress` <string>)
 * @example
 * (gz:decompress (gz:compress "Hi!"))
 * ; "Hi!"
 */
Expr::register('gz:decompress', function($args) {
    return gzuncompress ($args->get(1));
});

/**
 * Compresses a string using the Deflate algorithm.
 * @code (`gz:deflate` <string>)
 * @example
 * (gz:deflate "Hi!")
 * ; (binary data)
 */
Expr::register('gz:deflate', function($args) {
    return gzdeflate ($args->get(1));
});

/**
 * Inflates (decompresses) a string compressed using the Deflate algorithm.
 * @code (`gz:inflate` <string>)
 * @example
 * (gz:inflate (gz:deflate "Hi!"))
 * ; "Hi!"
 */
Expr::register('gz:inflate', function($args) {
    return gzinflate ($args->get(1));
});

/**
 * Converts a value to a JSON string.
 * @code (`json:str` <value>)
 * @example
 * (json:str (& name "John" "age" 35))
 * ; {"name":"John","age":35}
 */
Expr::register('json:str', function($args) {
    return JSON::stringify($args->get(1));
});

/**
 * Converts a value to a JSON string with indentation (pretty-print). Useful to debug nested structures.
 * @code (`json:dump` <value>)
 * @example
 * (json:dump (# 1 2 3))
 * ; [1,2,3]
 */
Expr::register('json:dump', function($args) {
    return JSON::prettify($args->get(1));
});

/**
 * Parses a JSON string and returns the value.
 * @code (`json:parse` <string>)
 * @example
 * (json:parse "[ 1, 2, 3 ]")
 * ; [1,2,3]
 */
Expr::register('json:parse', function($args) {
    return JSON::parse((string)$args->get(1));
});


/**
 * Generates a random UUID (Universally Unique Identifier) version 4.
 * @code (`utils:uuid`)
 * @example
 * (utils:uuid)
 * ; "550e8400-e29b-41d4-a716-446655440000"
 */
Expr::register('utils:uuid', function() {
    $data = random_bytes(16);
    $tmp = explode(' ', microtime());
    $tmp[0] = (int)($tmp[0] * 0xFFFF);
    $tmp[1] = (int)$tmp[1];
    $data[15] = chr(($tmp[0] & 0xFF00) >> 8);
    $data[14] = chr(($tmp[1] & 0xFF000000) >> 24);
    $data[13] = chr(($tmp[1] & 0x00FF0000) >> 16);
    $data[12] = chr(($tmp[0] & 0x00FF) >> 0);
    $data[11] = chr(($tmp[1] & 0x000000FF) >> 0);
    $data[10] = chr(($tmp[1] & 0x0000FF00) >> 8);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
});

function xmlToMap($xml)
{
    $namespaces = $xml->getDocNamespaces();
    $namespaces[''] = null;
 
    $attributes = new Map();
    foreach ($namespaces as $prefix => $namespace)
    foreach ($xml->attributes($namespace) as $attrName => $value)
        $attributes->set(($prefix ? $prefix . ':' : '') . $attrName, (string)$value);
 
    $children = new Arry();
    foreach ($namespaces as $prefix => $namespace)
    foreach ($xml->children($namespace) as $childXml)
    {
        $children->push(xmlToMap($childXml));
    }
 
    return new Map([
        'tagName' => $xml->getName(), 
        'attributes' => $attributes,
        'children' => $children,
        'textContent' => trim((string)$xml)
    ]);
}

function xmlSimplify ($xml, $parent)
{
    if ($xml->children->length() === 0 && $xml->attributes->length() === 0)
    {
        if (\Rose\typeOf($parent) === 'Rose\\Map')
            $parent->set($xml->tagName, $xml->textContent);
        else
            $parent->push($xml->textContent);

        return $parent;
    }

    $k = new Map();

    if (\Rose\typeOf($parent) === 'Rose\\Map')
        $parent->set($xml->tagName, $k);
    else
        $parent->push($k);

    if ($xml->attributes->length() != 0)
        $k->set('$', $xml->attributes);

    $xml->children->forEach(function($value) use (&$k)
    {
        if (!$k->has($value->tagName))
            $k->set($value->tagName, new Arry());

        xmlSimplify($value, $k->get($value->tagName));
    });

    return $parent;
}

/**
 * Parses an XML string and returns a map containing XML node information fields `tagName`, `attributes`,
 * `children` and `textContent`.
 * @code (`xml:parse` <xml>)
 * @example
 * (xml:parse "<root><item id='1'>Item 1</item><item id='2'>Item 2</item></root>")
 * ; {
 * ;     "tagName": "root",
 * ;     "attributes": {},
 * ;     "children": [
 * ;         {
 * ;             "tagName": "item",
 * ;             "attributes": {
 * ;                 "id": "1"
 * ;             },
 * ;             "children": [],
 * ;             "textContent": "Item 1"
 * ;         },
 * ;         {
 * ;             "tagName": "item",
 * ;             "attributes": {
 * ;                 "id": "2"
 * ;             },
 * ;             "children": [],
 * ;             "textContent": "Item 2"
 * ;         }
 * ;     ],
 * ;     "textContent": ""
 * ; }
 */
Expr::register('xml:parse', function($args)
{
    $value = (string)$args->get(1);
    if (Text::length($value) == 0) return null;

    $value = simplexml_load_string($value);
    $result = xmlToMap($value);

    $value = $value->getNamespaces();
    $value = sizeof($value) != 0 ? array_keys($value)[0] : null;

    if ($value)
        $result->tagName = $value . ':' . $result->tagName;

    return $result;
});

/**
 * Simplifies an XML node into a more easy to traverse structure. Any node with no children and no attributes
 * will be converted to a string with its text content. Nodes with children will be converted to a map with the
 * tag name as key and the children as value. If a node has attributes, they will be stored in a `$` key.
 * @code (`xml:simplify` <xml-node>)
 * @example
 * (xml:simplify (xml:parse "<root name=\"Test\"><item>Item 1</item><item>Item 2</item></root>"))
 * ; {
 * ;    "root": {
 * ;        "$": {
 * ;            "name": "Test"
 * ;        },
 * ;        "item": [
 * ;            "Item 1",
 * ;            "Item 2"
 * ;        ]
 * ;    }
 * ; 
 */
Expr::register('xml:simplify', function($args)
{
    $m = new Map();
    return $args->get(1) ? xmlSimplify($args->get(1), $m) : $m;
});

/**
 * Converts an array or map into an HTML table.
 * @code (`html:encode` <data>)
 * @example
 * (html:encode (# (& "Name" "John" "Age" 35) (& "Name" "Jane" "Age" 25)))
 * ; HTML table with two rows and two columns
 */
Expr::register('html:encode', function($args)
{
    Gateway::$contentType = 'text/html';
    $data = $args->get(1);

    if (\Rose\typeOf($data) == 'Rose\Data\Reader')
    {
        $s = '';

        foreach ($data->fields->__nativeArray as $name)
        {
            $s .= "<th>$name</th>";
        }

        $s = "<tr>$s</tr>";

        $data->forEach(function ($row) use (&$s)
        {
            $i = '';

            foreach ($row->__nativeArray as $col)
                $i .= "<td>$col</td>";

            $s .= '<tr>'.nl2br($i).'</tr>';
        });

        return "<table style='font-family: monospace;' border='1'>$s</table>";
    }

    if (\Rose\typeOf($data) == 'Rose\Arry')
    {
        if (!$data->length) return '';

        $s = '';

        foreach ($data->get(0)->keys()->__nativeArray as $name)
        {
            $s .= "<th>$name</th>";
        }

        $s = "<tr>$s</tr>";

        $data->forEach(function ($row) use (&$s)
        {
            $i = '';

            foreach ($row->__nativeArray as $col)
                $i .= "<td>$col</td>";

            $s .= '<tr>'.nl2br($i).'</tr>';
        });

        return "<table style='font-family: monospace;' border='1'>$s</table>";
    }

    if (\Rose\typeOf($data) == 'Rose\Map')
    {
        $s = '';

        $data->forEach(function ($value, $key) use (&$s)
        {
            $s .= '<tr>'.nl2br("<th>$key</th><td>$value</td>").'</tr>';
        });

        return "<table style='font-family: monospace;' border='1'>$s</table>";
    }

    return $data;
});


/**
 * Returns the version of the framework.
 * @code (`sys:version`)
 * @example
 * (sys:version)
 * ; 5.0.1
 */
Expr::register('sys:version', function($args) {
    return \Rose\Main::version();
});

/**
 * Sleeps for the given number of seconds.
 * @code (`sys:sleep` <seconds>)
 * @example
 * (sys:sleep 0.5)
 * ; true
 */
Expr::register('sys:sleep', function($args) {
    $value = $args->get(1);
    if (\Rose\isInteger($value))
        sleep($args->get(1));
    else
        usleep($args->get(1) * 1e6);
    return true;
});

/**
 * Executes a shell command and returns the complete output as a string.
 * @code (`sys:shell` <command>)
 * @example
 * (sys:shell "ls -l")
 * ; "total 0\n-rw-r--r-- 1 user user 0 Jan  1 00:00 file.txt\n"
 */
Expr::register('sys:shell', function($args) {
    return shell_exec ($args->get(1));
});

/**
 * Executes a command and returns the exit code.
 * @code (`sys:exec` <command>)
 * @example
 * (sys:exec "ls -l")
 * ; 0
 */
Expr::register('sys:exec', function($args) {
    $result = 0;
    passthru ($args->get(1), $result);
    return $result;
});

/**
 * Runs the garbage collector.
 * @code (`sys:gc`)
 * @example
 * (sys:gc)
 * ; 1
 */
Expr::register('sys:gc', function() {
    return gc_collect_cycles();
});

/**
 * Returns the memory peak usage in megabytes.
 * @code (`sys:peak-memory`)
 * @example
 * (sys:peak-memory)
 * ; 4.58
 */
Expr::register('sys:peak-memory', function() {
    return Math::fixed(memory_get_peak_usage() / 1048576, 2);
});

/**
 * Object used to access language strings. The strings are stored in the `strings` directory in the root of the project.
 * @code (`strings`)
 * @example
 * (strings.messages)
 * ; (value of the `messages` file located in `strings/messages.conf`)
 *
 * (strings.messages.welcome)
 * ; (value of the `welcome` key in the `strings/messages.conf` file)
 *
 * (strings.@messages.welcome)
 * ; (value of the `welcome` key in the `strings/en/messages.conf` file)
 */
Expr::register('strings', function ($args) {
    return Strings::getInstance();
});

/**
 * Returns or sets the current language for the strings extension. The folder should exist in the `strings` directory,
 * otherwise an error will be thrown.
 * @code (`strings:lang` [lang])
 * @example
 * (strings:lang)
 * ; "en"
 * (strings:lang "xx")
 * ; Error: Language code `xx` is not supported
 */
Expr::register('strings:lang', function ($args)
{
    if ($args->length == 1)
        return Strings::getInstance()->lang;
    
    $lang = $args->get(1);
    if (!Strings::getInstance()->setLang($lang))
        throw new ArgumentError('Language code `'.$lang.'` is not supported');

    return null;
});

/**
 * Returns a string given the path. If the target string is not found then the given path will be returned as a placeholder.
 * @code (`strings:get` [lang])
 * @example
 * (strings:get "messages.welcome")
 * ; "Welcome!"
 *
 * (strings:get "@messages.welcome")
 * ; "@messages.welcome"
 */
Expr::register('strings:get', function ($args) {
    return Strings::getInstance()->get($args->get(1));
});
