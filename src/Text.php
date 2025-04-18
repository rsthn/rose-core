<?php

namespace Rose;

use Rose\Errors\Error;
use Rose\Map;
use Rose\Arry;
use Rose\Strings;
use Rose\Regex;
use Rose\Expr;

// @title Text
// @desc Utility functions to manipulate text strings.

class Text
{
    /**
     * Ensures the given value is a string. If not, returns an empty string.
     */
    public static function str ($text) {
        if (!\Rose\isString($text))
            return '';
        return $text;
    }

    /**
     * Returns a substring of a given string. Negative values in 'start' indicate to start from the end of the string.
     */
    public static function substring ($text, $start, $length=null, $unicode=false)
    {
        $text = self::str($text);
        $n = Text::length($text, $unicode);

        if ($start < 0) $start += $n;
        if ($length < 0) $length = $length + $n - $start;
        if ($length === null) $length = $n - $start;

        $text = $unicode ? mb_substr($text, $start, $length, 'utf-8') : substr($text, $start, $length);
        return $text === false ? '' : $text;
    }

    /**
     * Returns a substring of a given string. Negative values in 'start' or 'end' are treated as offsets from the end of the string.
     */
    public static function slice ($text, $start, $end=null, $unicode=false)
    {
        $text = self::str($text);
        $n = Text::length($text, $unicode);

        if ($start < 0) $start += $n;
        if ($end < 0) $end += $n;
        if ($end === null) $end = $n;

        if ($end <= $start)
            return '';

        $text = $unicode ? mb_substr($text, $start, $end-$start, 'utf-8') : substr($text, $start, $end-$start);
        return $text === false ? '' : $text;
    }

    /**
     * Converts the string to upper case.
     */
    public static function toUpperCase ($text, $unicode=false) {
        $text = self::str($text);
        return !$unicode ? strtoupper($text) : mb_strtoupper($text, 'utf-8');
    }

    /**
     * Converts the string to lower case.
     */
    public static function toLowerCase ($text, $unicode=false) {
        $text = self::str($text);
        return !$unicode ? strtolower($text) : mb_strtolower($text, 'utf-8');
    }

    /**
     * Returns the position of a sub-string in the given text. Returns `false` when not found.
     */
    public static function indexOf ($text, $needle, $offset=0, $unicode=false) {
        $text = self::str($text);
        $n = self::length($text, $unicode);
        return Math::abs($offset) > $n ? false : (
            !$unicode ? strpos($text, $needle, $offset) : mb_strpos($text, $needle, $offset, 'utf-8')
        );
    }

    public static function lastIndexOf ($text, $value, $offset=0, $unicode=false) {
        $text = self::str($text);
        $n = self::length($text, $unicode);
        return Math::abs($offset) > $n ? false : (
            !$unicode ? strrpos($text, $value, $offset) : mb_strrpos($text, $value, $offset, 'utf-8')
        );
    }

    /**
     * Returns the length of the given text.
     */
    public static function length ($text, $unicode=null) {
        $text = self::str($text);
        return !$unicode ? strlen($text) : mb_strlen($text, 'utf-8');
    }

    /**
     * Removes white space (or any of the given chars) and returns the result.
     */
    public static function trim ($text, $chars=null) {
        $text = self::str($text);
        if ($chars != null)
            return \trim($text, $chars);
        else
            return \trim($text, " \n\r\f\t\v\x00");
    }

    public static function ltrim ($text, $chars=null) {
        $text = self::str($text);
        if ($chars != null)
            return \ltrim($text, $chars);
        else
            return \ltrim($text, " \n\r\f\t\v\x00");
    }

    public static function rtrim ($text, $chars=null) {
        $text = self::str($text);
        if ($chars != null)
            return \rtrim($text, $chars);
        else
            return \rtrim($text, " \n\r\f\t\v\x00");
    }

    /**
     * Returns boolean indicating if the given text starts with the given value.
     */
    public static function startsWith ($text, $value, $unicode=false) {
        return Text::substring($text, 0, Text::length($value, $unicode), $unicode) === $value;
    }

    /**
     * Returns boolean indicating if the given text ends with the given value.
     */
    public static function endsWith ($text, $value, $unicode=false) {
        return Text::substring($text, -Text::length($value, $unicode), null, $unicode) === $value;
    }

    /**
     * Returns the reverse of the given text.
     */
    public static function reverse ($text) {
        $text = self::str($text);
        return strrev($text);
    }

    /**
     * Replaces a string (a) for another (b) in the given text.
     */
    public static function replace ($a, $b, ?string $text) {
        $text = self::str($text);
        return str_replace ($a, $b, $text);
    }

    /**
     * Splits a string and returns the slices.
     */
    public static function split ($delimiter, $text, $unicode=false)
    {
        $text = self::str($text);
        if ($delimiter != '')
            return Arry::fromNativeArray(\explode($delimiter, $text));
        else
            return Arry::fromNativeArray(str_split($text));
    }

    /**
     * Pads a value by adding a character to the left until it reaches the desired length.
     */
    public static function lpad ($value, $len, $char=' ') {
        return \str_pad($value, $len, $char, STR_PAD_LEFT);
    }

    /**
     * Pads a value by adding a character to the right until it reaches the desired length.
     */
    public static function rpad ($value, $len, $char=' ') {
        return \str_pad($value, $len, $char, STR_PAD_RIGHT);
    }


    /**
     * Returns the number of occurrences of the given value in the string.
     */
    public static function substr_count ($str, $value, $unicode=false) {
        if (!$unicode)
            return \substr_count($str, $value);
        else
            return mb_substr_count($str, $value, 'utf-8');
    }

    /**
     * Converts the specified value to a string representation.
     */
    public static function toString ($value) {
        if (\Rose\isString($value))
            return $value;
        if ($value === null) return '';
        if ($value === false) return 'false';
        if ($value === true) return 'true';
        return (string)$value;
    }

    /**
     * Compares two strings and returns negative if a < b, zero (0) if a == b, and positive if a > b.
     */
    public static function compare ($a, $b) {

        /* function mb_strcmp($str1, $str2, $encoding = 'UTF-8') {
            $len1 = mb_strlen($str1, $encoding);
            $len2 = mb_strlen($str2, $encoding);
        
            $minLen = min($len1, $len2);
            for ($i = 0; $i < $minLen; $i++) {
                $char1 = mb_substr($str1, $i, 1, $encoding);
                $char2 = mb_substr($str2, $i, 1, $encoding);
        
                $diff = strcmp($char1, $char2);
                if ($diff !== 0) {
                    return $diff;
                }
            }
            return $len1 - $len2;
        } */
        return strcmp($a, $b);
    }

    public static function translate ($str, $from, $to, $unicode=false)
    {
        if (!$unicode)
            return strtr($str, $from, $to);

        if (mb_strlen($from, 'utf-8') !== mb_strlen($to, 'utf-8'))
            throw new InvalidArgumentException('[str:tr] the `from` and `to` strings must have the same length');
    
        for ($i = 0; $i < mb_strlen($from, 'utf-8'); $i++) {
            $str = mb_ereg_replace(
                mb_substr($from, $i, 1, 'utf-8'),
                mb_substr($to, $i, 1, 'utf-8'),
                $str
            );
        }
    
        return $str;
    }
};


/**
 * @deprecated Use `str:sub` or `str:slice` instead.
 * Returns a substring of a given string. Negative values in `start` are treated as offsets from the end of the string.
 * @code (`str:sub` <start> [count] <value>)
 * @code (`substr` <start> [count] <value>)
 * @example
 * (str:sub 1 2 "hello")
 * ; "el"
 *
 * (str:sub -4 2 "world")
 * ; "or"
 *
 * (str:sub -3 "hello")
 * ; "llo"
 *
 * (str:sub 2 -2 "Привет!")
 * ; "иве"
 */
Expr::register('substr', function ($args)
{
    \Rose\trace('[WARN] Using `substr` is deprecated. Use `str:sub` instead.');
    $s = (string)$args->get($args->length-1);
    $start = 0;
    $count = null;

    if ($args->length == 4) {
        $start = (int)($args->get(1));
        $count = (int)($args->get(2));
    }
    else {
        $start = (int)($args->get(1));
        $count = null;
    }

    return Text::substring($s, $start, $count, true);
});

Expr::register('str:sub', function ($args)
{
    $s = (string)$args->get($args->length-1);
    $start = 0;
    $count = null;

    if ($args->length == 4) {
        $start = (int)($args->get(1));
        $count = (int)($args->get(2));
    }
    else {
        $start = (int)($args->get(1));
        $count = null;
    }

    return Text::substring($s, $start, $count, true);
});

/**
 * Returns a substring of a given string. Negative values in `start` or `end` are treated as offsets from the end of the string.
 * @code (`str:slice` <start> [end] <value>)
 * @example
 * (str:slice 1 2 "hello")
 * ; "e"
 *
 * (str:slice -4 3 "world")
 * ; "or"
 *
 * (str:slice 2 -1 "Привеt!")
 * ; "ивеt"
 */
Expr::register('str:slice', function ($args)
{
    $s = (string)$args->get($args->length-1);
    $start = 0;
    $end = null;

    if ($args->length == 4) {
        $start = (int)($args->get(1));
        $end = (int)($args->get(2));
    }
    else {
        $start = (int)($args->get(1));
        $end = null;
    }

    return Text::slice($s, $start, $end, true);
});

/**
 * Returns the number of occurrences of the given value in the string.
 * @code (`str:count` <value|array> <string>)
 * @example
 * (str:count "l" "hello")
 * ; 2
 *
 * (str:count ["l" "h"] "hello")
 * ; 3
 */
Expr::register('str:count', function($args)
{
    $value = $args->get(1);
    $type = \Rose\typeOf($value);
    if ($type !== 'Rose\\Arry')
        return Text::substr_count($args->get(2), $args->get(1), true);

    $n = 0;
    foreach ($value->__nativeArray as $val)
        $n += Text::substr_count($args->get(2), $val, true);
    return $n;
});


/**
 * Pads a value by adding a character to the left until it reaches the desired length. If no padding character
 * is provided, it defaults to a space.
 * @code (`lpad` <length> [pad] <string>)
 * @example
 * (lpad 5 "0" "123")
 * ; 00123
 *
 * (lpad 5 "123")
 * ; ..123
 */
Expr::register('lpad', function($args) {
    if ($args->length > 3)
        return Text::lpad($args->get(3), $args->get(1), $args->get(2));
    else
        return Text::lpad($args->get(2), $args->get(1));
});

/**
 * Pads a value by adding a character to the right until it reaches the desired length. If no padding character
 * is provided, it defaults to a space.
 * @code (`rpad` <length> [pad] <string>)
 * @example
 * (rpad 5 "0" "123")
 * ; 12300
 * 
 * (rpad 5 "123")
 * ; 123..
 */
Expr::register('rpad', function($args) {
    if ($args->length > 3)
        return Text::rpad($args->get(3), $args->get(1), $args->get(2));
    else
        return Text::rpad($args->get(2), $args->get(1));
});

/**
 * Converts the value to upper case.
 * @code (`upper` <value>)
 * @example
 * (upper "hello")
 * ; "HELLO"
 */
Expr::register('upper', function ($args) {
    return Text::toUpperCase($args->get(1), true);
});

/**
 * Converts the value to lower case.
 * @code (`lower` <value>)
 * @example
 * (lower "HELLO")
 * ; "hello"
 */
Expr::register('lower', function ($args) {
    return Text::toLowerCase($args->get(1), true);
});

/**
 * Removes white space (or any of the given chars) and returns the result.
 * @code (`trim` [chars] <value>)
 * @example
 * (trim "  hello  ")
 * ; "hello"
 */
Expr::register('trim', function ($args) {
    $chars = $args->length > 2 ? $args->get(1) : null;
    $val = $args->get($args->length-1);
    return Text::trim($val, $chars);
});

/**
 * Removes white space (or any of the given chars) from the left and returns the result.
 * @code (`ltrim` [chars] <value>)
 * @example
 * (ltrim "  hello  ")
 * ; "hello  "
 */
Expr::register('ltrim', function ($args) {
    $chars = $args->length > 2 ? $args->get(1) : null;
    $val = $args->get($args->length-1);
    return Text::ltrim($val, $chars);
});

/**
 * Removes white space (or any of the given chars) from the right and returns the result.
 * @code (`rtrim` [chars] <value>)
 * @example
 * (rtrim "  hello  ")
 * ; "  hello"
 */
Expr::register('rtrim', function ($args) {
    $chars = $args->length > 2 ? $args->get(1) : null;
    $val = $args->get($args->length-1);
    return Text::rtrim($val, $chars);
});

/**
 * Returns boolean indicating if the given text starts with the given value.
 * @code (`starts-with?` <value> <text> [value-true=true] [value-false=false])
 * @example
 * (starts-with? "hello" "hello world")
 * ; true
 */
Expr::register('_starts-with?', function($parts, $data) {
    return Text::startsWith(Expr::value($parts->get(2), $data), Expr::value($parts->get(1), $data), true)
        ? ($parts->has(3) ? Expr::value($parts->get(3), $data) : true)
        : ($parts->has(4) ? Expr::value($parts->get(4), $data) : false)
        ;
});

/**
 * Returns boolean indicating if the given text ends with the given value.
 * @code (`ends-with?` <value> <text> [value-true=true] [value-false=false])
 * @example
 * (ends-with? "world" "hello world")
 * ; true
 */
Expr::register('_ends-with?', function($parts, $data) {
    return Text::endsWith(Expr::value($parts->get(2), $data), Expr::value($parts->get(1), $data), true)
        ? ($parts->has(3) ? Expr::value($parts->get(3), $data) : true)
        : ($parts->has(4) ? Expr::value($parts->get(4), $data) : false)
        ;
});

/**
 * Returns the number of **characters** in the given text.
 * @code (`str:len` <value>)
 * @example
 * (str:len "hello")
 * ; 5
 * (str:len "你好")
 * ; 2
 * (strlen "Привет!")
 * ; 7
 */
Expr::register('str:len', function($args) {
    return Text::length((string)$args->get(1), true);
});

/**
 * Replaces all occurences of `a` with `b` in the given value.
 * @code (`str:replace` <search> <replacement> <value>)
 * @example
 * (str:replace "hello" "world" "hello world")
 * ; "world world"
 */
Expr::register('str:replace', function ($args)
{
    $search = $args->get(1);
    $replacement = $args->get(2);
    return Text::replace($search, $replacement, $args->get(3));
});


/**
 * Returns the index of a sub-string in the given text. Returns -1 when not found.
 * @code (`str:index` <search> <value>)
 * @example
 * (str:index "world" "hello world")
 * ; 6
 */
Expr::register('str:index', function ($args) {
    $i = Text::indexOf($args->get(2), $args->get(1), 0, true);
    return $i === false ? -1 : $i;
});

/**
 * Returns the last index of a sub-string in the given text. Returns -1 when not found.
 * @code (`str:last-index` <search> <value>)
 * @example
 * (str:last-index "world" "hello world world")
 * ; 12
 */
Expr::register('str:last-index', function ($args) {
    $i = Text::lastIndexOf($args->get(2), $args->get(1), 0, true);
    return $i === false ? -1 : $i;
});

/**
 * Compares two strings and returns negative if a < b, zero (0) if a == b, and positive if a > b.
 * @code (`str:compare` <a> <b>)
 * @example
 * (str:compare "a" "b")
 * ; -1
 *
 * (str:compare "b" "a")
 * ; 1
 *
 * (str:compare "a" "a")
 * ; 0
 */
Expr::register('str:compare', function ($args) {
    return Text::compare($args->get(1), $args->get(2));
});

/**
 * Translates characters in the given string.
 * @code (`str:tr` <source-set> <replacement-set> <value>)
 * @example
 * (str:tr "abc" "123" "cabc")
 * ; 3123
 */
Expr::register('str:tr', function($args) {
    return Text::translate ($args->get(3), $args->get(1), $args->get(2), true);
});




/**
 * Returns the length of the given binary buffer.
 * @code (`buf:len` <value>)
 * @example
 * (buf:len "hello")
 * ; 5
 *
 * (buf:len "Привет!")
 * ; 13
 */
Expr::register('buf:len', function ($args)
{
    return Text::length((string)$args->get(1), false);
});

/**
 * Returns a substring of a binary string. Negative values in `start` are treated as offsets from the end of the string.
 * @code (`buf:sub` <start> [count] <value>)
 * @example
 * (buf:sub 1 2 "hello")
 * ; "el"
 *
 * (buf:sub -4 2 "world")
 * ; "or"
 */
Expr::register('buf:sub', function ($args)
{
    $s = (string)$args->get($args->length-1);
    $start = 0;
    $count = null;

    if ($args->length == 4) {
        $start = (int)($args->get(1));
        $count = (int)($args->get(2));
    }
    else {
        $start = (int)($args->get(1));
        $count = null;
    }

    return Text::substring($s, $start, $count, false);
});

/**
 * Returns a slice of a binary buffer. Negative values in `start` or `end` are treated as offsets from the end of the string.
 * @code (`buf:slice` <start> [end] <value>)
 * @example
 * (buf:slice 1 2 "hello")
 * ; "e"
 *
 * (buf:slice -4 3 "world")
 * ; "or"
 *
 * (buf:slice 1 (+ 1 4) "universe")
 * ; "nive"
 * 
 */
Expr::register('buf:slice', function ($args)
{
    $s = (string)$args->get($args->length-1);
    $start = 0;
    $end = null;

    if ($args->length == 4) {
        $start = (int)($args->get(1));
        $end = (int)($args->get(2));
    }
    else {
        $start = (int)($args->get(1));
        $end = null;
    }

    return Text::slice($s, $start, $end, false);
});

/**
 * Compares two binary strings and returns negative if a < b, zero (0) if a == b, and positive if a > b.
 * @code (`buf:compare` <a> <b>)
 * @example
 * (buf:compare "a" "b")
 * ; -1
 *
 * (buf:compare "b" "a")
 * ; 1
 *
 * (buf:compare "a" "a")
 * ; 0
 */
Expr::register('buf:compare', function ($args) {
    return strcmp($args->get(1), $args->get(2));
});

/**
 * Returns the octet values of the characters in the given binary string.
 * @code (`buf:bytes` <value>)
 * @example
 * (buf:bytes "ABC")
 * ; [65,66,67]
 *
 * (buf:bytes "Любовь")
 * ; [208,155,209,142,208,177,208,190,208,178,209,140]
 */
Expr::register('buf:bytes', function($args) {
    return Text::split('', $args->get(1))->map(function($value) {
        return ord($value);
    });
});

/**
 * Returns the binary string corresponding to the given bytes.
 * @code (`buf:from-bytes` <octet-list>)
 * @example
 * (buf:from-bytes (# 65 66 67))
 * ; ABC
 *
 * (buf:from-bytes (# 237 140 140 235 158 128 236 131 137))
 * ; 파란색
 */
Expr::register('buf:from-bytes', function($args) {
    return $args->get(1)->map(function($value) {
        return chr($value);
    })->join('');
});

/**
 * Returns the binary representation of the given 8-bit unsigned integer or reads an 8-bit unsigned integer from the binary string.
 * @code (`buf:uint8` <int-value>)
 * @code (`buf:uint8` <string-value> [offset=0])
 * @example
 * (buf:uint8 0x40)
 * ; "@"
 * 
 * (buf:uint8 "@")
 * ; 0x40
 */
Expr::register('buf:uint8', function($args) {
    $value = $args->get(1);
    if (!\Rose\isString($value)) {
        $value = (int)$value;
        return chr($value & 0xFF);
    }

    $value = Text::substring($value, $args->{2} ?? 0, 1);
    if (Text::length($value) != 1)
        throw new Error('Invalid string length for uint8');

    return ord($value);
});

/**
 * Returns the binary representation of the given 16-bit unsigned integer (little endian) or reads a 16-bit unsigned integer from the binary string.
 * @code (`buf:uint16` <int-value>)
 * @code (`buf:uint16` <string-value> [offset=0])
 * @example
 * (buf:uint16 0x4041)
 * ; "A@"
 * 
 * (buf:uint16 "A@")
 * ; 0x4041
 */
Expr::register('buf:uint16', function($args) {
    $value = $args->get(1);
    if (!\Rose\isString($value)) {
        $value = (int)$value;
        return chr($value & 0xFF) . chr($value >> 8);
    }

    $value = Text::substring($value, $args->{2} ?? 0, 2);
    if (Text::length($value) != 2)
        throw new Error('Invalid string length for uint16');
    return ord($value[1]) << 8 | ord($value[0]);
});

/**
 * Returns the binary representation of the given 16-bit unsigned integer (big endian) or reads a 16-bit unsigned integer from the binary string.
 * @code (`buf:uint16be` <int-value>)
 * @code (`buf:uint16be` <string-value> [offset=0])
 * @examplee
 * (buf:uint16b 0x4041)
 * ; "@A"
 * 
 * (buf:uint16be "@A")
 * ; 0x4041
 */
Expr::register('buf:uint16be', function($args) {
    $value = $args->get(1);
    if (!\Rose\isString($value)) {
        $value = (int)$value;
        return chr($value >> 8) . chr($value & 0xFF);
    }

    $value = Text::substring($value, $args->{2} ?? 0, 2);
    if (Text::length($value) != 2)
        throw new Error('Invalid string length for uint16be');
    return ord($value[0]) << 8 | ord($value[1]);
});

/**
 * Returns the binary representation of the given 32-bit unsigned integer (little endian) or reads a 32-bit unsigned integer from the binary string.
 * @code (`buf:uint32` <int-value>)
 * @code (`buf:uint32` <string-value> [offset=0])
 * @example
 * (buf:uint32 0x40414243)
 * ; "CBA@"
 * 
 * (buf:uint32 "CBA@")
 * ; 0x40414243
 */
Expr::register('buf:uint32', function($args) {
    $value = $args->get(1);
    if (!\Rose\isString($value)) {
        $value = (int)$value;
        return chr($value & 0xFF) . chr(($value >> 8) & 0xFF) . chr(($value >> 16) & 0xFF) . chr($value >> 24);
    }

    $value = Text::substring($value, $args->{2} ?? 0, 4);
    if (Text::length($value) != 4)
        throw new Error('Invalid string length for uint32');
    return ord($value[3]) << 24 | ord($value[2]) << 16 | ord($value[1]) << 8 | ord($value[0]);
});

/**
 * Returns the binary representation of the given 32-bit unsigned integer (big endian) or reads a 32-bit unsigned integer from the binary string.
 * @code (`buf:uint32be` <int-value>)
 * @code (`buf:uint32be` <string-value> [offset=0])
 * @example
 * (buf:uint32be 0x40414243)
 * ; "@ABC"
 * 
 * (buf:uint32be "@ABC")
 * ; 0x40414243
 */
Expr::register('buf:uint32be', function($args) {
    $value = $args->get(1);
    if (!\Rose\isString($value)) {
        $value = (int)$value;
        return chr($value >> 24) . chr(($value >> 16) & 0xFF) . chr(($value >> 8) & 0xFF) . chr($value & 0xFF);
    }

    $value = Text::substring($value, $args->{2} ?? 0, 4);
    if (Text::length($value) != 4)
        throw new Error('Invalid string length for uint32be');
    return ord($value[0]) << 24 | ord($value[1]) << 16 | ord($value[2]) << 8 | ord($value[3]);
});
