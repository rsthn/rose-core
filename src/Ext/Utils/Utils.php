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

Expr::register('strings', function ($args) {
    if ($args->length == 1)
        return Strings::getInstance();
    return Strings::get($args->get(1));
});

Expr::register('strings:lang', function ($args) {
    if ($args->length == 1)
        return Strings::getInstance()->lang;
    Strings::getInstance()->setLang($args->get(1));
    return null;
});


/**
 * Sleeps for the given number of seconds.
 * @code (`sys:sleep` <seconds>)
 * @example
 * (sys:sleep 1)
 * ; true
 */
Expr::register('sys:sleep', function($args) {
    sleep($args->get(1));
    return true;
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
 * @code (`env:set` <name> <value>)
 * @example
 * (env:set "HOME" "/home/user")
 * ; true
 */
Expr::register('env:set', function($args) {
    return putenv($args->slice(1)->join(''));
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
 * @code (`url-query:stringify` <fields>)
 * @example
 * (url-query:stringify (& name "John" "age" 35))
 * ; "name=John&age=35"
 */
Expr::register('url-query:stringify', function($args) {
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
 * @code (`json:stringify` <value>)
 * @example
 * (json:stringify (# 1 2 3))
 * ; [1,2,3]
 */
Expr::register('json:stringify', function($args) {
    $value = $args->get(1);
    if (\Rose\typeOf($value) == 'Rose\\Arry' || \Rose\typeOf($value) == 'Rose\\Map')
        return (string)$value;
    return JSON::stringify($value);
});

/**
 * Converts a value to a JSON string with indentation (pretty). Useful with nested structures.
 * @code (`json:prettify` <value>)
 * @example
 * (json:prettify (# 1 2 3))
 * ; [1, 2, 3]
 */
Expr::register('json:prettify', function($args) {
    $value = $args->get(1);
    if (\Rose\typeOf($value) === 'primitive')
        return JSON::stringify($value);
    return JSON::prettify(JSON::parse((string)$value));
});

/**
 * Parses a JSON string and returns the value.
 * @code (`json:parse` <string>)
 * @example
 * (json:parse "[ 1, 2, 3 ]")
 * ; [1,2,3]
 */
Expr::register('json:parse', function($args) {
    $value = (string)$args->get(1);
    if (Text::length($value) == 0) return null;
    return $value[0] == '[' ? Arry::fromNativeArray(JSON::parse($value)) : ($value[0] == '{' ? Map::fromNativeArray(JSON::parse($value)) : JSON::parse($value));
});









Expr::register('utils:random-bytes', function($args) { return random_bytes((int)$args->get(1)); });
Expr::register('utils:randstr', function($args) { return bin2hex(random_bytes((int)$args->get(1))); });
Expr::register('utils:randstr-base64', function($args) { return base64_encode(random_bytes((int)$args->get(1))); });
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

Expr::register('utils:unique', function($args)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789._';

    if ($args->has(2)) {
        $chars = $args->get(2);
        if (Text::length($chars) != 64)
            throw new ArgumentError('Code charset string should be 64 characters long.');
    }

    $tmp = explode(' ', microtime());
    $tmp[0] = ((int)($tmp[0] * 0x1000000)) & 0xFFFFFF;
    $tmp[1] = ((int)$tmp[1]) & 0xFFFFFFFF;

    $data = [
        (($tmp[1] >> 24) & 0x3F),
        (($tmp[1] >> 0) & 0x3F),
        (($tmp[1] >> 12) & 0x3F),
        (($tmp[1] >> 18) & 0x3F),
        (($tmp[1] >> 6) & 0x3F),
        (($tmp[0] >> 6) & 0x3F),
        (((($tmp[1] >> 30) & 0x03) << 4) | (($tmp[0] >> 12) & 0x0F)),
        (($tmp[0] >> 0) & 0x3F),
    ];

    $n = $args->length > 1 ? (int)$args->get(1) : 0;
    while ($n-- > 8)
        $data[] = ord(random_bytes(1)) & 0x3F;

    $tmp = '';
    for ($i = 0; $i < count($data); $i++)
        $tmp .= $chars[ $data[$i] ^ (ord(random_bytes(1)) & 0x3F) ];

    return $tmp;
});





Expr::register('dump+fmt', function($args)
{
    $value = $args->get(1);

    if (\Rose\typeOf($value) === 'primitive')
        return JSON::stringify($value);

    return JSON::prettify(JSON::parse((string)$value));
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

Expr::register('xml:simplify', function($args)
{
    $m = new Map();
    return $args->get(1) ? xmlSimplify($args->get(1), $m) : $m;
});

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

/* ************ */
Expr::register('array:new', function($args)
{
    $array = new Arry();

    for ($i = 1; $i < $args->length; $i++)
        $array->push($args->get($i));

    return $array;
});

/**
 * array:sort [<a-var>] [<b-var>] <array-expr> <block>
 */
Expr::register('_array:sort', function($parts, $data)
{
    $a_name = 'a';
    $b_name = 'b';
    $i = 1;

    Expr::takeIdentifier($parts, $data, $i, $a_name);
    Expr::takeIdentifier($parts, $data, $i, $b_name);

    $list = Expr::expand($parts->get($i++), $data, 'arg');
    if (!$list || \Rose\typeOf($list) != 'Rose\Arry')
        throw new \Rose\Errors\Error('(array:sort) invalid array expression');

    $block = $parts->slice($i);

    $a_present = false; $a_value = null;
    if ($data->has($a_name)) {
        $a_present = true;
        $a_value = $data->get($a_name);
    }

    $b_present = false; $b_value = null;
    if ($data->has($b_name)) {
        $b_present = true;
        $b_value = $data->get($b_name);
    }

    $list->sortx(function($a, $b) use(&$a_name, &$b_name, &$data, &$block) {
        $data->set($a_name, $a);
        $data->set($b_name, $b);
        return Expr::blockValue($block, $data);
    });

    if ($a_present) $data->set($a_name, $a_value);
    else $data->remove($a_name);

    if ($b_present) $data->set($b_name, $b_value);
    else $data->remove($b_name);

    return $list;
});

Expr::register('array:sort-asc', function($args)
{
    $array = $args->get(1);
    $array->sort('ASC');
    return $array;
});

Expr::register('array:sort-desc', function($args)
{
    $array = $args->get(1);
    $array->sort('DESC');
    return $array;
});

Expr::register('array:sortl-asc', function($args)
{
    $array = $args->get(1);
    $array->sortl('ASC');
    return $array;
});

Expr::register('array:sortl-desc', function($args)
{
    $array = $args->get(1);
    $array->sortl('DESC');
    return $array;
});

Expr::register('array:push', function($args)
{
    $array = $args->get(1);

    for ($i = 2; $i < $args->length; $i++)
        $array->push($args->get($i));

    return $array;
});

Expr::register('array:unshift', function($args)
{
    $array = $args->get(1);

    for ($i = 2; $i < $args->length; $i++)
        $array->unshift($args->get($i));

    return $array;
});

Expr::register('array:pop', function($args)
{
    return $args->get(1)->pop();
});

Expr::register('array:shift', function($args)
{
    return $args->get(1)->shift();
});

Expr::register('array:first', function($args)
{
    return $args->get(1)->first();
});

Expr::register('array:last', function($args)
{
    return $args->get(1)->last();
});

Expr::register('array:remove', function($args)
{
    return $args->get(1)->remove((int)$args->get(2));
});

Expr::register('array:indexOf', function($args)
{
    return $args->get(1)->indexOf($args->get(2));
});

Expr::register('array:lastIndexOf', function($args)
{
    return $args->get(1)->lastIndexOf($args->get(2));
});

Expr::register('array:length', function($args)
{
    return $args->get(1)->length();
});

Expr::register('array:append', function($args)
{
    return $args->get(1)->append($args->get(2));
});

Expr::register('array:merge', function($args)
{
    return $args->get(1)->merge($args->get(2));
});

Expr::register('array:unique', function($args)
{
    return $args->get(1)->unique();
});

Expr::register('array:reverse', function($args)
{
    return $args->get(1)->reverse();
});

Expr::register('array:clear', function($args)
{
    return $args->get(1)->clear();
});

Expr::register('array:clone', function($args)
{
    return $args->get(1)->replicate($args->{2} ?? false);
});

/**
 * 	array:flatten <depth> <array>
 * 	array:flatten <array>
 */
Expr::register('array:flatten', function($args)
{
    if ($args->length == 3)
        return $args->get(2)->flatten($args->get(1));
    else
        return $args->get(1)->flatten();
});

/**
 * 	array:slice <start> <length> <array>
 * 	array:slice <start> <array>
 */
Expr::register('array:slice', function($args)
{
    if ($args->length == 4)
        return $args->get(3)->slice($args->get(1), $args->get(2));
    else
        return $args->get(2)->slice($args->get(1));
});



/* ************ */
Expr::register('map:new', function($args)
{
    $map = new Map();

    for ($i = 1; $i+1 < $args->length; $i += 2)
        $map->set($args->get($i), $args->get($i+1));

    return $map;
});

Expr::register('map:sort-asc', function($args)
{
    $map = $args->get(1);
    $map->sort('ASC');
    return $map;
});

Expr::register('map:sort-desc', function($args)
{
    $map = $args->get(1);
    $map->sort('DESC');
    return $map;
});

Expr::register('map:sortk-asc', function($args)
{
    $map = $args->get(1);
    $map->sortk('ASC');
    return $map;
});

Expr::register('map:sortk-desc', function($args)
{
    $map = $args->get(1);
    $map->sortk('DESC');
    return $map;
});

Expr::register('map:keys', function($args)
{
    $map = $args->get(1);
    return $map->keys();
});

Expr::register('map:values', function($args)
{
    $map = $args->get(1);
    return $map->values();
});

Expr::register('map:set', function($args)
{
    $map = $args->get(1);

    for ($i = 2; $i+1 < $args->length; $i+=2)
        $map->set($args->get($i), $args->get($i+1));

    return null;
});

Expr::register('map:get', function($args)
{
    $map = $args->get(1);
    return $map->{$args->get(2)};
});

Expr::register('map:has', function($args)
{
    $map = $args->get(1);
    return $map->has($args->get(2));
});

Expr::register('map:remove', function($args)
{
    return $args->get(1)->remove((string)$args->get(2));
});

Expr::register('map:keyOf', function($args)
{
    return $args->get(1)->keyOf($args->get(2));
});

Expr::register('map:length', function($args)
{
    return $args->get(1)->length();
});

Expr::register('map:assign', function($args)
{
    $m = $args->get(1);

    for ($i = 2; $i < $args->length(); $i++)
        $m = $m->merge($args->get($i), true);

    return $m;
});

Expr::register('map:merge', function($args)
{
    $m = $args->get(1);

    for ($i = 2; $i < $args->length(); $i++)
        $m = $m->merge($args->get($i));

    return $m;
});

Expr::register('map:clear', function($args)
{
    return $args->get(1)->clear();
});
