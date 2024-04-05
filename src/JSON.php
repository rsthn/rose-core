<?php

namespace Rose;

use Rose\Text;

/**
 * JSON utility functions.
 */

class JSON
{
    /**
     * Converts an object to a JSON string.
     * @param mixed $value
     * @return string
     */
    public static function stringify ($value)
    {
        if (\Rose\typeOf($value) === 'Rose\\Arry' || \Rose\typeOf($value) === 'Rose\\Map')
            return (string)$value;

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Converts an object to a pretty-print JSON string.
     * @param mixed $value
     * @return string
     */
    public static function prettify ($value)
    {
        if (\Rose\typeOf($value) === 'primitive')
            return JSON::stringify($value);

        $value = JSON::parse((string)$value);
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Parses a JSON string.
     * @param string $value
     * @return mixed
     */
    public static function parse ($value)
    {
        if (Text::length($value) == 0)
            return null;

        return $value[0] === '[' 
            ? Arry::fromNativeArray(json_decode($value, true)) 
            : ($value[0] == '{' 
                    ? Map::fromNativeArray(json_decode($value, true)) 
                    : json_decode($value, true)
            );
    }
};
