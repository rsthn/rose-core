<?php

namespace Rose;

use Rose\Errors\Error;
use Rose\Map;
use Rose\Arry;
use Rose\Strings;
use Rose\Regex;
use Rose\Expr;

// @title Strings
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
    public static function substring ($text, $start, $length=null)
    {
        $text = self::str($text);
        $n = Text::length($text);

        if ($start < 0) $start += $n;
        if ($length < 0) $length = $length + $n - $start;
        if ($length === null) $length = $n - $start;

        $text = substr($text, $start, $length);
        return $text === false ? '' : $text;
    }

    /**
     * Converts the string to upper case.
     */
    public static function toUpperCase ($text, $encoding=null) {
        $text = self::str($text);
        return !$encoding ? strtoupper($text) : mb_strtoupper($text, $encoding);
    }

    /**
     * Converts the string to lower case.
     */
    public static function toLowerCase ($text, $encoding=null) {
        $text = self::str($text);
        return !$encoding ? strtolower($text) : mb_strtolower($text, $encoding);
    }

    /**
     * Converts the first letter in the word to upper case.
     */
    public static function upperCaseFirst ($text) {
        $text = self::str($text);
        return ucfirst($text);
    }

    /**
     * Returns the position of a sub-string in the given text. Returns `false` when not found.
     */
    public static function position ($text, $needle, $offset=0) {
        $text = self::str($text);
        $n = self::length($text);
        return Math::abs($offset) > $n ? false : strpos($text, $needle, $offset);
    }

    public static function indexOf ($text, $value) {
        $text = self::str($text);
        return strpos($text, $value);
    }

    public static function revIndexOf ($text, $value, $offset=0) {
        $text = self::str($text);
        $n = self::length($text);
        return Math::abs($offset) > $n ? false : strrpos($text, $value, $offset);
    }

    /**
     * Returns the length of the given text.
     */
    public static function length ($text, $encoding=null) {
        $text = self::str($text);
        return !$encoding ? strlen($text) : mb_strlen($text, $encoding);
    }

    /**
     * Removes white space (or any of the given chars) and returns the result.
     */
    public static function trim ($text, $chars=null) {
        $text = self::str($text);
        if ($chars != null)
            return \trim ($text, $chars);
        else
            return \trim ($text, " \n\r\f\t\v\x00");
    }

    /**
     * Returns boolean indicating if the given text starts with the given value.
     */
    public static function startsWith ($text, $value) {
        return Text::substring($text, 0, Text::length($value)) == $value;
    }

    /**
     * Returns boolean indicating if the given text ends with the given value.
     */
    public static function endsWith ($text, $value) {
        return Text::substring($text, -Text::length($value)) == $value;
    }

    /**
     * Returns the reverse of the given text.
     */
    public static function reverse ($text) {
        $text = self::str($text);
        return strrev ($text);
    }

    /**
     * Replaces a string (a) for another (b) in the given text.
     */
    public static function replace ($a, $b, ?string $text) {
        $text = self::str($text);
        return str_replace ($a, $b, $text);
    }

    /**
     * Truncates a string and adds ellipsis if its length is greater than the specified maximum.
     */
    public static function truncate ($value, $maxLength) {
        $value = self::str($value);
        if (Text::length($value) > $maxLength)
            return Text::substring ($value, 0, $maxLength-3) . '...';
        return $value;
    }

    /**
     * Splits a string and returns the slices.
     */
    public static function split ($delimiter, $text)
    {
        $text = self::str($text);

        if ($delimiter != '')
            return Arry::fromNativeArray(\explode ($delimiter, $text));
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
        return strcmp ($a, $b);
    }
};


/**
 * Returns a substring of a given string. Negative values in `start` indicate to start from the end of the string.
 * @code (`substr` <start> [count] <value>)
 * @example
 * (substr 1 2 "hello")
 * ; "el"
 *
 * (substr -4 2 "world")
 * ; "or"
 *
 * (substr -3 "hello")
 * ; "llo"
 *
 * (substr 2 -2 "goodbye")
 * ; "odb"
 */
Expr::register('substr', function ($args)
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

    return Text::substring($s, $start, $count);
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
    return Text::toUpperCase($args->get(1), 'utf8');
});

/**
 * Converts the value to lower case.
 * @code (`lower` <value>)
 * @example
 * (lower "HELLO")
 * ; "hello"
 */
Expr::register('lower', function ($args) {
    return Text::toLowerCase($args->get(1), 'utf8');
});

/**
 * Converts the first letter in the word to upper case.
 * @code (`upper-first` <value>)
 * @example
 * (upper-first "hello")
 * ; "Hello"
 */
Expr::register('upper-first', function ($args) {
    return Text::upperCaseFirst($args->get(1));
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
 * Returns boolean indicating if the given text starts with the given value.
 * @code (`starts-with` <value> <text>)
 * @example
 * (starts-with "hello" "hello world")
 * ; true
 */
Expr::register('starts-with', function($args) {
    return Text::startsWith($args->get(2), $args->get(1));
});

/**
 * Returns boolean indicating if the given text ends with the given value.
 * @code (`ends-with` <value> <text>)
 * @example
 * (ends-with "world" "hello world")
 * ; true
 */
Expr::register('ends-with', function($args) {
    return Text::endsWith($args->get(2), $args->get(1));
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
    return Text::length((string)$args->get(1), 'utf8');
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

    return Expr::apply($args->slice(3), function($value) use(&$search, &$replacement) {
        return Text::replace($search, $replacement, $value);
    });
});


/**
 * Returns the index of a sub-string in the given text. Returns -1 when not found.
 * @code (`str:index` <search> <value>)
 * @example
 * (str:index "world" "hello world")
 * ; 6
 */
Expr::register('str:index', function ($args) {
    $i = Text::indexOf($args->get(2), $args->get(1));
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
    $i = Text::revIndexOf($args->get(2), $args->get(1));
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
    return strtr ($args->get(3), $args->get(1), $args->get(2));
});

/**
 * Returns the octet values of the characters in the given string.
 * @code (`str:bytes` <value>)
 * @example
 * (str:bytes "ABC")
 * ; [65,66,67]
 *
 * (str:bytes "Любовь")
 * ; [208,155,209,142,208,177,208,190,208,178,209,140]
 */
Expr::register('str:bytes', function($args) {
    return Text::split('', $args->get(1))->map(function($value) {
        return ord($value);
    });
});

/**
 * Returns the string corresponding to the given binary values.
 * @code (`str:from-bytes` <octet-list>)
 * @example
 * (str:from-bytes (# 65 66 67))
 * ; ABC
 *
 * (str:from-bytes (# 237 140 140 235 158 128 236 131 137))
 * ; 파란색
 */
Expr::register('str:from-bytes', function($args) {
    return $args->get(1)->map(function($value) {
        return chr($value);
    })->join('');
});
