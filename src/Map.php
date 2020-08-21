<?php
/*
**	Rose\Map
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

use Rose\Regex;
use Rose\Text;
use Rose\Expr;
use Rose\Arry;

/*
**	Generic container for name-indexed items only, this class is an associative-array.
*/

class Map
{
	/*
	**	The actual Map contents (implemented as native array).
	*/
    public $__nativeArray;

	/*
	**	Constructs an instance of a Map.
	*/
    public function __construct ($nativeArray=null, $recursiveScan=true)
    {
		$this->__nativeArray = $nativeArray ? $nativeArray : array();

		if ($nativeArray == null || !$recursiveScan)
			return;

		foreach ($this->__nativeArray as &$value)
		{
			if (is_array($value))
			{
				if (count(array_filter(array_keys($value), 'is_string')) != 0)
					$value = Map::fromNativeArray($value);
				else
					$value = Arry::fromNativeArray($value);
			}
		}
    }

	/*
	**	Builds a new map using a native array.
	*/
    public static function fromNativeArray ($nativeArray, $recursiveScan=true)
    {
		if ($nativeArray === null)
			return null;

        $newMap = new Map();
		$newMap->__nativeArray = $nativeArray;

        if ($recursiveScan)
        {
            foreach ($newMap->__nativeArray as &$value)
            {
                if (is_array($value))
                {
                    if (count(array_filter(array_keys($value), 'is_string')) != 0)
                        $value = Map::fromNativeArray($value);
                    else
                        $value = Arry::fromNativeArray($value);
                }
            }
		}

        return $newMap;
    }

	/*
	**	Builds a new Map by combining the given two arrays.
	*/
    public static function fromCombination ($keys, $values)
    {
		return new Map (array_combine(is_array($keys) ? $keys : $keys->__nativeArray, is_array($values) ? $values : $values->__nativeArray), false);
    }

	/*
	**	Sorts the map by value in ascending or descending order.
	*/
    public function sort ($order='ASC')
    {
        if ($order == 'ASC')
            asort ($this->__nativeArray);
        else
			arsort ($this->__nativeArray);

        return $this;
    }

	/*
	**	Sorts the map by key in ascending or descending order.
	*/
    public function sortk ($order='ASC')
    {
        if ($order == 'ASC')
            ksort ($this->__nativeArray);
        else
			krsort ($this->__nativeArray);

        return $this;
    }

	/*
	**	Returns the length of the Map.
	*/
    public function length ()
    {
        return sizeof($this->__nativeArray);
    }

	/*
	**	Returns an array with the keys of the items defined in the map.
	*/
    public function keys ()
    {
        return new Arry(array_keys($this->__nativeArray), false);
    }

	/*
	**	Returns an array with the values of the items defined in the map.
	*/
    public function values ()
    {
        return new Arry(array_values($this->__nativeArray), false);
    }

	/*
	**	Creates and returns a replica of the Map.
	*/
    public function replicate ($deep=false)
    {
        $newMap = new Map();
		$newMap->__nativeArray = $this->__nativeArray;

        if ($deep)
        {
            foreach ($newMap->__nativeArray as $key => $value)
            {
                if (typeOf($value) == 'Rose\\Arry' || typeOf($value) == 'Rose\\Map')
                    $newMap->set ($key, $value->replicate(true));
			}
		}

        return $newMap;
    }

	/*
	**	Returns the key of the element whose value matches or null if not found, if the strict parameter is set, it will search for an identical element.
	*/
    public function keyOf ($value, $strict=false)
    {
        $result = array_search($value, $this->__nativeArray, $strict);
        return $result === false ? null : $result;
    }

	/*
	**	Checks if the given key exists in the map.
	*/
    public function has ($key)
    {
		if ($key != null) {
			if ($key[0] == '#' || $key == 'length')
				return true;

			if ($key[0] == '@')
				return $this->has(Text::substring($key, 1));
		}

        return array_key_exists($key, $this->__nativeArray) ? true : false;
    }

	/*
	**	Sets an item in the map.
	*/
    public function set ($key, $value)
    {
        $this->__nativeArray[$key] = $value;
        return $this;
    }

	/*
	**	Returns the value given a key, or null if doesn't exist.
	*/
    public function get ($key)
    {
		if (!$this->has($key))
			return null;

        return $this->__nativeArray[$key];
    }

	/*
	**	Returns a new map that is the result of merging the current map with the given map.
	*/
    public function merge ($map, $mergeInSelf=false)
    {
        if ($mergeInSelf === true)
        {
            $this->__nativeArray = array_merge ($this->__nativeArray, $map->__nativeArray);
            return $this;
		}

        return new Map(array_merge($this->__nativeArray, $map->__nativeArray), false);
    }

	/*
	**	Maps each value of the map to another value using the specified filter function.
	*/
    public function map ($filter)
    {
		foreach ($this->__nativeArray as $name => &$item)
		{
			$item = $filter($item, $name);
		}

        return $this;
	}

	/*
	**	Returns a new array containing only elements that were accepted by the given filter function (by returning true).
	*/
    public function filter ($filter)
    {
		$tmp = new Arry();

		foreach ($this->__nativeArray as $name => &$item)
		{
			if ($filter($item, $name))
				$tmp->push($item);
		}

        return $tmp;
	}

	/*
	**	For each of the items in the map the specified function is called.
	*/
    public function forEach ($function)
    {
		foreach ($this->__nativeArray as $key => &$item)
		{
			$function ($item, $key, $this);
		}

        return $this;
    }

	/*
	**	Calls Text.format with the given string and two parameters which are the key and value of each item in the map.
	*/
    public function format ($formatString)
    {
		$result = new Arry();

		$temp = new Arry (array(null, null), false);

        foreach ($this->__nativeArray as $key => $value)
        {
            $temp->set (0, $key);
			$temp->set (1, $value);

            $result->push (Text::format($formatString, $temp));
		}

        return $result;
    }

	/*
	**	Clears the contents of the map.
	*/
    public function clear ()
    {
        $this->__nativeArray = array();
        return $this;
    }

	/*
	**	Removes all the items that match the given pattern, it will match using the key if useKey is set to true. Returns the map.
	*/
    public function removeAll ($pattern, $useKey=false)
    {
        if (!$useKey)
        {
            foreach ($this->__nativeArray as $key => $value)
            {
                if (Regex::_matches ($pattern, $value))
                    $this->remove ($key);
			}
        }
        else
        {
            foreach ($this->__nativeArray as $key => $value)
            {
                if (Regex::_matches ($pattern, $key))
                    $this->remove ($key);
            }
		}

        return $this;
    }

	/*
	**	Selects all the items that match the given pattern, it will match the key if useKey is set to true. Returns a new map.
	*/
    public function selectAll ($pattern, $useKey=false)
    {
		$result = new Map();

        if (!$useKey)
        {
            foreach ($this->__nativeArray as $key => $value)
            {
                if (Regex::_matches ($pattern, $value))
                    $result->set ($key, $value);
            }
        }
        else
        {
            foreach ($this->__nativeArray as $key => $value)
            {
                if (Regex::_matches ($pattern, $key))
                    $result->set ($key, $value);
            }
		}

        return $result;
    }

	/*
	**	Removes an item from the Map and returns the item or null if it was not found.
	*/
    public function remove ($key)
    {
        $item = $this->get($key);
		unset($this->__nativeArray[$key]);

        return $item;
    }

	/*
	**	Definition of the global accessor for items, this will be invoked when the map is used with the arrow operator and
	**	the attribute does not exist in the class definition.
	**
	**	If the requested field starts with '#' will be considered to be an 'exists' call, and will return 0 or 1.
	**
	**	If it starts with '@' will be considered a regular 'get' call. This was added because this function also provides
	**	access to builtin 'length', therefore you wanted to actually access a field named 'length' use '@length' instead.
	*/
    public function __get ($name)
    {
        switch ($name)
        {
            case 'length':
				return sizeof($this->__nativeArray);

            default:
                if ($name[0] == '#')
                    return $this->has(Text::substring($name, 1)) ? '1' : '0';

                if ($name[0] == '@')
                    return $this->get(Text::substring($name, 1));

				return $this->get($name);
        }
    }

	/*
	**	Definition of the global setter for items.
	*/
    public function __set ($name, $value)
    {
        $this->set($name, $value);
    }

	/*
	**	Returns the string representation of the map.
	*/
    public function __toString ()
    {
		$s = array();

		foreach ($this->__nativeArray as $name => $item)
		{
			$name = json_encode($name).':';

			switch (typeOf($item, true))
			{
				case 'Rose\\Arry':
				case 'Rose\\Map':
					$s[] = $name . (string)$item;
					break;

				case 'null':
					$s[] = $name . 'null';
					break;

				case 'bool':
					$s[] = $name . ($item ? 'true' : 'false');
					break;
	
				case 'int':
				case 'number':
					$s[] = $name . $item;
					break;

				case 'string':
					$s[] = $name . json_encode($item);
					break;

				default:
					$s[] = $name . json_encode((string)$item);
					break;
			}
		}

		return '{' . implode(',', $s) . '}';
    }
};
