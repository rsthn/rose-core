<?php

namespace Rose;

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
    public static function stringify ($value) {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Converts an object to a pretty-print JSON string.
     * @param mixed $value
     * @return string
     */
    public static function prettify ($value) {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Parses a JSON string.
     * @param string $value
     * @return mixed
     */
    public static function parse ($value) {
        return json_decode($value, true);
    }
};
