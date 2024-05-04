<?php

namespace Rose;

use Rose\Expr;

// @title Math

abstract class Math
{
    /**
     * Returns true if the given value is in the range defined by [min, max], otherwise false will be returned.
     */
    public static function inrange ($value, $min, $max) {
        return ($min <= $value) && ($value <= $max);
    }

    /**
     * Returns a pseudo random number between 0 and 65535.
     */
    public static function rand() {
        return mt_rand() & 65535;
    }

    /**
     * Returns the maximum value that can be returned by rand().
     */
    public static function randmax() {
        return mt_getrandmax();
    }

    /**
     * Returns the absolute value of the given value.
     */
    public static function abs ($value) {
        return \abs($value);
    }

    /**
     * Returns the number rounded up to the nearest integer.
     */
    public static function round ($a) {
        return floor($a + 0.5);
    }

    /**
     * Returns the number rounded to the specified number of decimals.
     */
    public static function fixed ($value, $decimals) {
        return round($value, $decimals);
    }

    /**
     * Returns the ceiling value of a given number.
     */
    public static function ceil ($value) {
        return \ceil($value);
    }

    /**
     * Returns the floor value of a given number.
     */
    public static function floor ($value) {
        return \floor($value);
    }

    /**
     * Returns the minimum value of the given numbers.
     */
    public static function min ($a, $b) {
        return ($a < $b) ? $a : $b;
    }

    /**
     * Returns the maximum value of the given numbers.
     */
    public static function max ($a, $b) {
        return ($a > $b) ? $a : $b;
    }

    /**
     * Clamps the given value to the range defined by [a, b].
     */
    public static function clamp ($value, $a=-32767, $b=32767) {
        return ($value < $a) ? $a : (($value > $b) ? $b : $value);
    }

    /**
     * Aligns the given value to the nearest multiple of the given size.
     */
    public static function align ($value, $size)
    {
        $m = (int)($value / $size);
        if ($value % $size)
            $m++;
        return $m * $size;
    }

    /**
     * Converts a number to a hexadecimal string.
     */
    public static function toHex ($value, $size=2) {
        $value = dechex($value);
        return str_pad($value, self::align(Text::length($value), $size), '0', STR_PAD_LEFT);
    }

    /**
     * Converts a number to a binary string.
     */
    public static function toBin ($value, $size=8) {
        $value = decbin($value);
        return str_pad($value, self::align(Text::length($value), $size), '0', STR_PAD_LEFT);
    }

    /**
     * Converts a number to an octal string.
     */
    public static function toOct ($value, $size=3) {
        $value = decoct($value);
        return str_pad($value, self::align(Text::length($value), $size), '0', STR_PAD_LEFT);
    }

    /**
     * Returns a number from a hexadecimal string.
     */
    public static function fromHex ($value) {
        return hexdec($value);
    }

    /**
     * Returns a number from a binary string.
     */
    public static function fromBin ($value) {
        return bindec($value);
    }

    /**
     * Returns a number from an octal string.
     */
    public static function fromOct ($value) {
        return octdec($value);
    }
};


/**
 * Returns a pseudo random number between 0 and 65535.
 * @code (`math:rand`)
 * @example
 * (math:rand)
 * ; 4578
 */
Expr::register('math:rand', function() {
    return Math::rand();
});


/**
 * Returns the absolute value of the given value.
 * @code (`math:abs` <value>)
 * @example
 * (math:abs -5)
 * ; 5
 */
Expr::register('math:abs', function($args) {
    return Math::abs($args->get(1));
});


/**
 * Returns the number rounded up to the nearest integer.
 * @code (`math:round` <value>)
 * @example
 * (math:round 5.5)
 * ; 6
 *
 * (math:round 5.4)
 * ; 5
 */
Expr::register('math:round', function($args) {
    return Math::round($args->get(1));
});


/**
 * Returns the number rounded to the specified number of decimals.
 * @code (`math:fixed` <value> [decimals=2])
 * @example
 * (math:fixed 5.5112)
 * ; 5.51
 *
 * (math:fixed 5.4782)
 * ; 5.48
 */
Expr::register('math:fixed', function($args) {
    return Math::fixed($args->get(1), $args->{2} ?? 2);
});


/**
 * Returns the ceiling value of a given number.
 * @code (`math:ceil` <value>)
 * @example
 * (math:ceil 5.1)
 * ; 6
 */
Expr::register('math:ceil', function($args) {
    return Math::ceil($args->get(1));
});


/**
 * Returns the floor value of a given number.
 * @code (`math:floor` <value>)
 * @example
 * (math:floor 5.8)
 * ; 5
 */
Expr::register('math:floor', function($args) {
    return Math::floor($args->get(1));
});


/**
 * Clamps the given value to the range defined by [a, b].
 * @code (`math:clamp` <value> <min> <max>)
 * @example
 * (math:clamp 5 1 10)
 * ; 5
 *
 * (math:clamp 0 1 10)
 * ; 1
 *
 * (math:clamp 15 1 10)
 * ; 10
 */
Expr::register('math:clamp', function($args) {
    return Math::clamp($args->get(1));
});


/**
 * Aligns the given value to the nearest multiple of the given size.
 * @code (`math:align` <value> <size>)
 * @example
 * (math:align 5 3)
 * ; 6
 */
Expr::register('math:align', function($args) {
    return Math::align($args->get(1), $args->get(2));
});


/**
 * Converts a number to a hexadecimal string.
 * @code (`math:to-hex` <value> [size=2])
 * @example
 * (math:to-hex 255)
 * ; ff
 */
Expr::register('math:to-hex', function ($args) {
    return Math::toHex($args->get(1), $args->{2} ?? 2);
});


/**
 * Converts a number to a binary string.
 * @code (`math:to-bin` <value> [size=8])
 * @example
 * (math:to-bin 129)
 * ; 10000001
 */
Expr::register('math:to-bin', function ($args) {
    return Math::toBin($args->get(1), $args->{2} ?? 8);
});


/**
 * Converts a number to an octal string.
 * @code (`math:to-oct` <value> [size=3])
 * @example
 * (math:to-oct 15)
 * ; 17
 */
Expr::register('math:to-oct', function ($args) {
    return Math::toOct($args->get(1), $args->{2} ?? 3);
});


/**
 * Returns a number from a hexadecimal string.
 * @code (`math:from-hex` <value>)
 * @example
 * (math:from-hex "ff")
 * ; 255
 */
Expr::register('math:from-hex', function ($args) {
    return Math::fromHex($args->get(1));
});


/**
 * Returns a number from a binary string.
 * @code (`math:from-bin` <value>)
 * @example
 * (math:from-bin "10000001")
 * ; 129
 */
Expr::register('math:from-bin', function ($args) {
    return Math::fromBin($args->get(1));
});


/**
 * Returns a number from an octal string.
 * @code (`math:from-oct` <value>)
 * @example
 * (math:from-oct "17")
 * ; 15
 */
Expr::register('math:from-oct', function ($args) {
    return Math::fromOct($args->get(1));
});
