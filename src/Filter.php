<?php
/*
**	Rose\Session
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
use Rose\Text;

/*
**	Provides an interface to manipulate data types and formats.
*/

class Filter
{
	/*
	**	Available filter functions.
	*/
	private static $filterFunctions = null;

	/*
	**	Registers a filter function.
	*/
	public static function register ($name, $function)
	{
		if (Filter::$filterFunctions == null)
			Filter::$filterFunctions = new Map();

		Filter::$filterFunctions->set($name, $function);
	}

	/*
	**	Transforms the given value using a filter. If the filterName is an array, multiple transformations will be performed on the value.
	*/
    public static function filter ($filterName, $value)
    {
        if (typeOf($filterName) == 'Rose//Arry')
        {
            foreach ($filterName->__nativeArray as $filter)
                $value = Filter::filter($filter, $value);

            return $value;
		}

        return Filter::customFilter ($filterName, $value);
    }

    public static function fromJson ($data, $cslashes=false)
    {
        $data_b=json_decode(($cslashes?addcslashes($data,"\t\r\n"):$data),true);
        $data_b=_Map::fromNativeArray(($data_b?$data_b:array()));
        return (($data[0]=='[')?$data_b->values():$data_b);
    }

    public static function toJson ($data)
    {
        $result='';
        switch (typeOf($data))
        {
            case 'Array':
            foreach($data->__nativeArray as $item)
            {
                $result.=','.Filter::toJson($item);
            }
            $result='['._Text::substring($result,1).']';
            break;
            case 'Map':
            foreach($data->__nativeArray as $name=>$item)
            {
                $result.=','.Filter::filter('cescape',$name).':'.Filter::toJson($item);
            }
            unset ($name);
            $result='{'._Text::substring($result,1).'}';
            break;
            case 'PrimitiveType':
            if (isString($data))
            {
                $result=Filter::filter('cescape',$data);
            }
            else
            {
                $result=(($data===null)?'null':_Utils::Alt($data,'0'));
            }
            break;
        }
        return $result;
    }


    public static function toBase64 ($data)
    {
        return base64_encode($data);
    }

    public static function fromBase64 ($data)
    {
        return ((strlen($data)!=0)?base64_decode($data):'');
    }

    public static function toUtf8 ($data)
    {
        return utf8_encode($data);
    }

    public static function fromUtf8 ($data)
    {
        return ((strlen($data)!=0)?utf8_decode($data):'');
    }

    public static function toSerialized ($data)
    {
        return serialize($data);
    }

    public static function fromSerialized ($data)
    {
        return ((strlen($data)!=0)?unserialize($data):'');
    }

    public static function toGzip ($data)
    {
        return gzencode($data,9);
    }

    public static function fromGzip ($data)
    {
        return ((strlen($data)!=0)?gzdecode($data):'');
    }

    public static function toDeflate ($data)
    {
        return ((strlen($data)!=0)?gzdeflate($data,9):'');
    }

    public static function fromDeflate ($data)
    {
        return ((strlen($data)!=0)?gzinflate($data):'');
    }

    public static function toHexString ($data, $digits=null)
    {
        if (($digits==null))
        {
            return bin2hex($data);
        }
        $result='';
        $i=null;;
        $j=null;;
        $n=_Text::length($data);
        for ($i=0; ($i<$n); $i++)
        {
            $j=Filter::fromByte($data,$i);
            $result.=$digits[($j>>4)].$digits[($j&15)];
        }
        return $result;
    }

    public static function fromHexString ($data, $digits=null)
    {
        if (($digits==null))
        {
            try
            {
                return pack('H*',$data);
            }
            catch (_Exception $e)
            {
                return '';
            }
        }
        $result='';
        $i=null;;
        $n=(_Text::length($data)&~1);
        for ($i=0; ($i<$n); $i+=2)
        {
            $result.=Filter::toByte(((_Text::position($digits,$data[$i])*16)+_Text::position($digits,$data[($i+1)])));
        }
        return $result;
    }

    public static function toHexInteger ($data)
    {
        return sprintf('%x',$data);
    }

    public static function fromHexInteger ($data)
    {
        return hexdec($data);
    }

    public static function fromByte ($data, $index=0)
    {
        return ord($data[$index]);
    }

    public static function toByte ($value)
    {
        return chr($value);
    }

    public static function toWord ($value)
    {
        return pack('v',$value);
    }

    public static function toDword ($value)
    {
        return pack('V',$value);
    }

    public static function fromWord ($value)
    {
        $value=unpack('vx',$value);
        return $value['x'];
    }

    public static function fromDword ($value)
    {
        $value=unpack('Vx',$value);
        return $value['x'];
    }

    public static function toSigned ($data, $bits=8)
    {
        $mask=(((1<<$bits))-1);
        return ((($data&$mask))-(((((($data&$mask))<((($mask+1))>>1)))?0:($mask+1))));
    }

    public static function toUnsigned ($data, $bits=8)
    {
        return ((($data+((1<<$bits))))&((((1<<$bits))-1)));
    }
};
