<?php
/*
**	Rose\Text
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

use Rose\Map;
use Rose\Arry;
use Rose\Strings;
use Rose\Regex;

/*
**	Utility class to manipulate text strings.
*/

class Text
{
	/*
	**	Returns a substring of a given string. Negative values in 'start' indicate to start from the end of the string.
	*/
    public static function substring ($text, $start, $length=null)
    {
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
        return !$encoding ? strtoupper($text) : mb_strtoupper($text, $encoding);
    }

	/*
	**	Converts the string to lower case.
	*/
    public static function toLowerCase ($text, $encoding=null)
    {
        return !$encoding ? strtolower($text) : mb_strtolower($text, $encoding);
    }

	/*
	**	Converts the first letter in the word to upper case.
	*/
    public static function upperCaseFirst ($text)
    {
        return ucfirst ($text);
    }

	/*
	**	Converts the first letter in all words to upper case.
	*/
    public static function upperCaseWords ($text)
    {
        return ucwords ($text);
    }

	/*
	**	Returns the position of a sub-string in the given text.
	*/
    public static function position ($text, $needle, $offs=0)
    {
        return strpos($text, $needle, $offs);
    }

    public static function indexOf ($text, $value)
    {
        return strpos($text, $value);
    }

    public static function revIndexOf ($text, $value, $offset=0)
    {
        return strrpos($text, $value, $offset);
    }

	/*
	**	Returns the length of the given text.
	*/
    public static function length ($text, $encoding=null)
    {
        return !$encoding ? strlen($text) : mb_strlen($text, $encoding);
    }

	/*
	**	Removes white space (or any of the given chars) and returns the result.
	*/
    public static function trim ($text, $chars=null)
    {
        if ($chars != null)
            return \trim ($text, $chars);
        else
            return \trim ($text);
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
        return strrev ($text);
    }

	/*
	**	Replaces a string (a) for another (b) in the given text.
	*/
    public static function replace ($a, $b, $text)
    {
        return str_replace ($a, $b, $text);
    }













    public static function truncate ($text, $maxLength)
    {
        if ((Text::length($text)>$maxLength))
        {
            return Text::substring($text,0,($maxLength-3)).'...';
        }
        return $text;
    }

    public static function formatString ($fmt)
    {
        $args = Arry::fromNativeArray (func_get_args (), false)->slice (1);
        return vsprintf($fmt,$args->__nativeArray);
    }

    public static function split ($delimiter, $text, $allowEmpty=true)
    {
        if ((!$allowEmpty&&!$text))
        {
            return new Arry();
        }
        if (($delimiter!=''))
        {
            return Arry::fromNativeArray(call_user_func('explode',$delimiter,$text));
        }
        else
        {
            return Arry::fromNativeArray(str_split($text));
        }
    }

    public static function repeat ($text, $N)
    {
        return str_repeat($text,$N);
    }


    public static function blanks ($text)
    {
        $result='';
        $prev='';
        $n=Text::length($text);
        $i=null;;
        for ($i=0; ($i<$n); $i++)
        {
            switch ($text[$i])
            {
                case ' ':
                case '\t':
                case "\r":
                case "\n":
                case "\f":
                case "\v":
                if (($prev==' '))
                {
					break;
                }
                else
                {
                    $result.=' ';
                }
                $prev=' ';
                break;
                default:
                $result.=($prev=$text[$i]);
                break;
            }
        }
        return $result;
    }

    public static function padString ($text, $len, $str=' ')
    {
        return str_pad($text,$len,$str);
    }
};
