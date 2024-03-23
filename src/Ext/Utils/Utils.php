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

Expr::register('config', function ($args) { return Configuration::getInstance(); });

Expr::register('config:parse', function ($args) {
    return Configuration::loadFromBuffer($args->get(1));
});

Expr::register('config:stringify', function ($args) {
    return Configuration::saveToBuffer($args->get(1));
});

Expr::register('strings', function ($args)
{
    if ($args->length == 1)
        return Strings::getInstance();

    return Strings::get($args->get(1));
});

Expr::register('strings:lang', function ($args)
{
    if ($args->length == 1)
        return Strings::getInstance()->lang;

    Strings::getInstance()->setLang($args->get(1));
    return null;
});

Expr::register('resources', function ($args) { return Resources::getInstance(); });

Expr::register('gateway', function ($args) { return Gateway::getInstance(); });
Expr::register('gateway:redirect', function ($args) { return Gateway::redirect($args->get(1)); });
Expr::register('gateway:flush', function ($args) { return Gateway::flush(); });
Expr::register('gateway:persistent', function ($args) { return Gateway::persistent(); });
Expr::register('gateway:timeout', function ($args) { return Gateway::setTimeout($args->get(1)); });

Expr::register('math:to-hex', function ($args) { return Math::toHex($args->get(1)); });
Expr::register('math:to-bin', function ($args) { return Math::toBin($args->get(1)); });
Expr::register('math:to-oct', function ($args) { return Math::toOct($args->get(1)); });
Expr::register('math:from-hex', function ($args) { return Math::fromHex($args->get(1)); });
Expr::register('math:from-bin', function ($args) { return Math::fromBin($args->get(1)); });
Expr::register('math:from-oct', function ($args) { return Math::fromOct($args->get(1)); });

Expr::register('utils:gc', function() { gc_collect_cycles(); });
Expr::register('utils:getenv', function($args) { return $args->has(1) ? getenv($args->get(1)) : getenv(); });
Expr::register('utils:putenv', function($args) { putenv($args->slice(1)->join('')); return null; });

Expr::register('utils:random-bytes', function($args) { return random_bytes((int)$args->get(1)); });

Expr::register('utils:rand', function() { return Math::rand(); });
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

Expr::register('utils:sleep', function($args) { sleep($args->get(1)); return null; });

Expr::register('base64:encode', function($args) { return base64_encode ($args->get(1)); });
Expr::register('base64:decode', function($args) { return base64_decode ($args->get(1)); });

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

Expr::register('base64u:encode', function($args) { return base64url_encode ($args->get(1)); });
Expr::register('base64u:decode', function($args) { return base64url_decode ($args->get(1)); });

Expr::register('hex:encode', function($args) { return bin2hex ($args->get(1)); });
Expr::register('hex:decode', function($args) { return hex2bin ($args->get(1)); });

Expr::register('url:encode', function($args) { return urlencode ($args->get(1) ?? ''); });
Expr::register('url:decode', function($args) { return urldecode ($args->get(1) ?? ''); });

Expr::register('url-query:stringify', function($args) {
    return $args->get(1)->map(function($value, $key) { return urlencode($key).'='.urlencode($value); })->values()->join('&');
});

Expr::register('html-text:encode', function($args) { return htmlspecialchars ($args->get(1)); });
Expr::register('html-text:decode', function($args) { return htmlspecialchars_decode ($args->get(1)); });

Expr::register('gz:compress', function($args) { return gzcompress ($args->get(1)); });
Expr::register('gz:decompress', function($args) { return gzuncompress ($args->get(1)); });
Expr::register('gz:deflate', function($args) { return gzdeflate ($args->get(1)); });
Expr::register('gz:inflate', function($args) { return gzinflate ($args->get(1)); });

/**
 * (utils:lpad <length> [<pad>] <string>)
 */
Expr::register('utils:lpad', function($args)
{
    if ($args->length > 3)
        return Text::lpad($args->get(3), $args->get(1), $args->get(2));
    else
        return Text::lpad($args->get(2), $args->get(1));
});

/**
 * (utils:rpad <length> [<pad>] <string>)
 */
Expr::register('utils:rpad', function($args)
{
    if ($args->length > 3)
        return Text::rpad($args->get(3), $args->get(1), $args->get(2));
    else
        return Text::rpad($args->get(2), $args->get(1));
});

/**
 * (utils:lpad+0 <length> <string>)
 * (utils:rpad+0 <length> <string>)
 */
Expr::register('utils:lpad+0', function($args) { return Text::lpad($args->get(2), $args->get(1), '0'); });
Expr::register('utils:rpad+0', function($args) { return Text::rpad($args->get(2), $args->get(1), '0'); });

Expr::register('json:stringify', function($args)
{
    $value = $args->get(1);

    if (\Rose\typeOf($value) == 'Rose\\Arry' || \Rose\typeOf($value) == 'Rose\\Map')
        return (string)$value;

    return JSON::stringify($value);
});

Expr::register('json:prettify', function($args)
{
    $value = $args->get(1);

    if (\Rose\typeOf($value) === 'primitive')
        return JSON::stringify($value);

    return JSON::prettify(JSON::parse((string)$value));
});

Expr::register('dump+fmt', function($args)
{
    $value = $args->get(1);

    if (\Rose\typeOf($value) === 'primitive')
        return JSON::stringify($value);

    return JSON::prettify(JSON::parse((string)$value));
});

Expr::register('json:parse', function($args)
{
    $value = (string)$args->get(1);
    if (Text::length($value) == 0) return null;

    return $value[0] == '[' ? Arry::fromNativeArray(JSON::parse($value)) : ($value[0] == '{' ? Map::fromNativeArray(JSON::parse($value)) : JSON::parse($value));
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
    $data = $args->get(1);

    Wind::contentType(new Arry([null, 'text/html']));

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

/* ************************** */
Expr::register('utils:shell', function($args)
{
    return shell_exec ($args->get(1));
});

Expr::register('utils:exec', function($args)
{
    $result = 0;
    passthru ($args->get(1), $result);
    return $result;
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

/* ************ */
Expr::register('re:matches', function($args)
{
    return Regex::_matches($args->get(1), $args->get(2));
});

Expr::register('re:match-first', function($args)
{
    return Regex::_matchFirst($args->get(1), $args->get(2));
});

Expr::register('re:match', function($args)
{
    return Regex::_matchFirst($args->get(1), $args->get(2));
});

Expr::register('re:match-all', function($args)
{
    return Regex::_matchAll($args->get(1), $args->get(2), $args->{3} ?? 0);
});

Expr::register('re:split', function($args)
{
    return Regex::_split($args->get(1), $args->get(2));
});

/**
 * (re:replace <pattern> <replacement> <subject>)
 */
Expr::register('re:replace', function($args)
{
    return Regex::_replace($args->get(1), $args->get(2), $args->get(3));
});

Expr::register('re:extract', function($args)
{
    return Regex::_extract($args->get(1), $args->get(2));
});

Expr::register('re:get', function($args)
{
    return Regex::_getString($args->get(1), $args->get(2), $args->has(3) ? $args->get(3) : 0);
});
