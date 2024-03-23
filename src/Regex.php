<?php

namespace Rose;

use Rose\Arry;

/*
**	Provides an interface to manipulate and operate using regular expressions.
*/

class Regex
{
    /*
    **	Flag constants to be used with the matching methods.
    */
    public const PATTERN_ORDER = 1;
    public const SET_ORDER = 2;
    public const OFFSET_CAPTURE = 256;
    public const NO_EMPTY = 1;

    /*
    **	Regular expression pattern.
    */
    public $pattern;

    /*
    **	Constructs an instance of the Regex class, uses the given parameter as the internal regular expression pattern.
    */
    public function __construct ($pattern)
    {
        $this->pattern = $pattern;
    }

    /*
    **	Tests the regular expression pattern on the given text, returns true if it matches, or false otherwise.
    */
    public function matches ($text)
    {
        $text = Text::toString($text);
        return preg_match ($this->pattern, $text) == 0 ? false : true;
    }

    /*
    **	Tests the regular expression pattern on the given text, returns true if it matches, or false otherwise.
    */
    public static function _matches ($pattern, $text)
    {
        $text = Text::toString($text);
        return preg_match ($pattern, $text) == 0 ? false : true;
    }

    /*
    **	Returns an array containing the information of the first string that matches the pattern.
    */
    public function matchFirst ($text, $flags=0)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match ($this->pattern, $text, $result, $flags);
        return new Map ($result);
    }

    /*
    **	Returns an array containing the information of the first string that matches the pattern.
    */
    public static function _matchFirst ($pattern, $text, $flags=0)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match ($pattern, $text, $result, $flags);
        return new Map ($result);
    }

    /*
    **	Matches one text string and returns it. Returns null if no match found.
    */
    public function getString ($text, $captureIndex=0)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match ($this->pattern, $text, $result, 0);
        return sizeof($result) == 0 ? null : $result[$captureIndex];
    }

    /*
    **	Matches one text string and returns it. Returns null if no match found.
    */
    public static function _getString ($pattern, $text, $captureIndex=0)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match ($pattern, $text, $result, 0);
        return sizeof($result) == 0 ? null : $result[$captureIndex];
    }

    /*
    **	Uses the pattern and tries to match as many items as possible from the given text string. Returns an array with the matched items.
    */
    public function matchAll ($text, $captureIndex=0, $flags=Regex::PATTERN_ORDER)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match_all ($this->pattern, $text, $result, $flags);
        return new Arry ($captureIndex === true ? $result : $result[$captureIndex]);
    }

    /*
    **	Uses the pattern and tries to match as many items as possible from the given text string. Returns an array with the matched items.
    */
    public static function _matchAll ($pattern, $text, $captureIndex=0, $flags=Regex::PATTERN_ORDER)
    {
        $text = Text::toString($text);
        $result = null;
        preg_match_all ($pattern, $text, $result, $flags);
        return new Arry ($captureIndex === true ? $result : $result[$captureIndex]);
    }

    /*
    **	Splits the given string using the pattern as the delimiter, returns an array containing the split elements.
    */
    public function split ($text, $flags=0, $limit=-1)
    {
        $text = Text::toString($text);
        return new Arry (preg_split($this->pattern, $text, $limit, $flags));
    }

    /*
    **	Splits the given string using the pattern as the delimiter, returns an array containing the split elements.
    */
    public static function _split ($pattern, $text, $flags=0, $limit=-1)
    {
        $text = Text::toString($text);
        return new Arry (preg_split($pattern, $text, $limit, $flags));
    }

    /*
    **	Replaces all the strings that match the pattern by the given replacement.
    */
    public function replace ($text, $replacement='')
    {
        $text = Text::toString($text);
        return preg_replace ($this->pattern, $replacement, $text);
    }

    /*
    **	Replaces all the strings that match the pattern by the given replacement.
    */
    public static function _replace ($pattern, $replacement, $text)
    {
        $text = Text::toString($text);
        return preg_replace ($pattern, $replacement, $text);
    }

    /*
    **	Returns only the parts of the text that match the pattern.
    */
    public function extract ($text, $delim='', $captureIndex=0, $max=0)
    {
        if ($max)
            return $this->matchAll ($text, $captureIndex)->slice(0, $max)->join($delim);
        else
            return $this->matchAll ($text, $captureIndex)->join($delim);
    }

    /*
    **	Returns only the parts of the text that match the pattern.
    */
    public static function _extract ($pattern, $text, $delim='', $captureIndex=0, $max=0)
    {
        if ($max)
            return Regex::_matchAll ($pattern, $text, $captureIndex)->slice(0, $max)->join($delim);
        else
            return Regex::_matchAll ($pattern, $text, $captureIndex)->join($delim);
    }

    /*
    **	Returns the string representation of the class.
    */
    public function __toString ()
    {
        return $this->pattern;
    }
};
