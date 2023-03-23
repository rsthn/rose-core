<?php

namespace Rose;

/*
**	Provides a few basic math methods.
*/

abstract class Math
{
	/*
	**	Returns true if the given value is in the range defined by [min, max], otherwise false will be returned.
	*/
    public static function inrange ($value, $min, $max)
    {
        return ($min <= $value) && ($value <= $max);
    }

	/*
	**	Returns a pseudo random number between 0 and 65535.
	*/
    public static function rand()
    {
        return mt_rand() & 65535;
    }

	/*
	**	Returns the maximum value that can be returned by rand().
	*/
    public static function randmax()
    {
        return mt_getrandmax();
    }

	/*
	**	Returns the absolute value of the given value.
	*/
    public static function abs ($value)
    {
        return \abs($value);
    }

	/*
	**	Returns the number rounded up to the nearest integer.
	*/
    public static function round ($a)
    {
        return floor($a + 0.5);
    }

	/*
	**	Returns the ceil value of the given value.
	*/
    public static function ceil ($value)
    {
        return \ceil($value);
    }

	/*
	**	Returns the floor value of the given value.
	*/
    public static function floor ($value)
    {
        return \floor($value);
    }

	/*
	**	Returns the minimum value of the given numbers.
	*/
    public static function min ($a, $b)
    {
        return ($a < $b) ? $a : $b;
    }

	/*
	**	Returns the maximum value of the given numbers.
	*/
    public static function max ($a, $b)
    {
        return ($a > $b) ? $a : $b;
    }

	/*
	**	Returns the signed truncated value to the given interval.
	*/
    public static function truncate ($value, $a=-32767, $b=32767)
    {
        return ($value < $a) ? $a : (($value > $b) ? $b : $value);
    }

	/*
	**	Converts a number to hexadecimal.
	*/
    public static function toHex ($value)
    {
        return dechex($value);
    }

	/*
	**	Converts a number to binary.
	*/
    public static function toBin ($value)
    {
        return decbin($value);
    }

	/*
	**	Converts a number to octal.
	*/
    public static function toOct ($value)
    {
        return decoct($value);
    }

	/*
	**	Converts a number from hexadecimal.
	*/
    public static function fromHex ($value)
    {
        return hexdec($value);
    }

	/*
	**	Converts a number from binary.
	*/
    public static function fromBin ($value)
    {
        return bindec($value);
    }

	/*
	**	Converts a number from octal.
	*/
    public static function fromOct ($value)
    {
        return octdec($value);
    }
};
