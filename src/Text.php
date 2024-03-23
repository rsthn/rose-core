<?php

namespace Rose;

use Rose\Map;
use Rose\Arry;
use Rose\Strings;
use Rose\Regex;

/*
**	Utility class to manipulate text strings.
*/

class Text
{
    /**
     * 	Ensures the argument is a string.
     */
    public static function str ($text)
    {
        if (!\Rose\isString($text))
            return '';

        return $text;
    }

    /*
    **	Returns a substring of a given string. Negative values in 'start' indicate to start from the end of the string.
    */
    public static function substring ($text, $start, $length=null)
    {
        $text = self::str($text);

        if ($start < 0)
            $text = substr ($text, $start);
        else
        {
            if ($length === null)
                $text = substr ($text, $start);
            else
                $text = substr ($text, $start, $length);
        }

        return $text === false ? '' : $text;
    }

    /*
    **	Converts the string to upper case.
    */
    public static function toUpperCase ($text, $encoding=null)
    {
        $text = self::str($text);
        return !$encoding ? strtoupper($text) : mb_strtoupper($text, $encoding);
    }

    /*
    **	Converts the string to lower case.
    */
    public static function toLowerCase ($text, $encoding=null)
    {
        $text = self::str($text);
        return !$encoding ? strtolower($text) : mb_strtolower($text, $encoding);
    }

    /*
    **	Converts the first letter in the word to upper case.
    */
    public static function upperCaseFirst ($text)
    {
        $text = self::str($text);
        return ucfirst ($text);
    }

    /*
    **	Converts the first letter in all words to upper case.
    */
    public static function upperCaseWords ($text)
    {
        $text = self::str($text);
        return ucwords ($text);
    }

    /*
    **	Returns the position of a sub-string in the given text. Returns `false` when not found.
    */
    public static function position ($text, $needle, $offset=0)
    {
        $text = self::str($text);
        $n = self::length($text);

        return Math::abs($offset) > $n ? false : strpos($text, $needle, $offset);
    }

    public static function indexOf ($text, $value)
    {
        $text = self::str($text);
        return strpos($text, $value);
    }

    public static function revIndexOf ($text, $value, $offset=0)
    {
        $text = self::str($text);
        $n = self::length($text);

        return Math::abs($offset) > $n ? false : strrpos($text, $value, $offset);
    }

    /*
    **	Returns the length of the given text.
    */
    public static function length ($text, $encoding=null)
    {
        $text = self::str($text);
        return !$encoding ? strlen($text) : mb_strlen($text, $encoding);
    }

    /*
    **	Removes white space (or any of the given chars) and returns the result.
    */
    public static function trim ($text, $chars=null)
    {
        $text = self::str($text);

        if ($chars != null)
            return \trim ($text, $chars);
        else
            return \trim ($text, " \n\r\f\t\v\x00");
    }

    /*
    **	Returns boolean indicating if the given text starts with the given value.
    */
    public static function startsWith ($text, $value)
    {
        return Text::substring($text, 0, Text::length($value)) == $value;
    }

    /*
    **	Returns boolean indicating if the given text ends with the given value.
    */
    public static function endsWith ($text, $value)
    {
        return Text::substring($text, -Text::length($value)) == $value;
    }

    /*
    **	Returns the reverse of the given text.
    */
    public static function reverse ($text)
    {
        $text = self::str($text);
        return strrev ($text);
    }

    /*
    **	Replaces a string (a) for another (b) in the given text.
    */
    public static function replace ($a, $b, ?string $text)
    {
        $text = self::str($text);
        return str_replace ($a, $b, $text);
    }

    /*
    **	Truncates a string (and adds ellipsis) if its length is greater than the specified maximum.
    */
    public static function truncate ($value, $maxLength)
    {
        $value = self::str($value);

        if (Text::length($value) > $maxLength)
            return Text::substring ($value, 0, $maxLength-3) . '...';

        return $value;
    }

    /*
    **	Splits a string and returns the slices.
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
    public static function lpad ($value, $len, $char=' ')
    {
        return \str_pad($value, $len, $char, STR_PAD_LEFT);
/*
        $char = ((string)($char))[0];
        $value = (string)$value;
        $len = (int)$len;

        while (strlen($value) < $len)
            $value = $char.$value;

        return $value;
*/
    }

    /**
     * Pads a value by adding a character to the right until it reaches the desired length.
     */
    public static function rpad ($value, $len, $char=' ')
    {
        return \str_pad($value, $len, $char, STR_PAD_RIGHT);
/*
        $char = ((string)($char))[0];
        $value = (string)$value;
        $len = (int)$len;

        while (strlen($value) < $len)
            $value = $value.$char;

        return $value;
*/
    }

    /**
     * Converts the specified value to a string representation.
     */
    public static function toString ($value)
    {
        if (\Rose\isString($value))
            return $value;

        if ($value === null) return '';
        if ($value === false) return 'false';
        if ($value === true) return 'true';

        return (string)$value;
    }

    /**
     * Compares two strings and returns a value indicating if they are equal.
     */
    public static function compare ($a, $b)
    {
        return strcmp ($a, $b);
    }
};
