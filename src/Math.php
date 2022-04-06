<?php
/*
**	Rose\Math
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

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
