<?php
/*
**	Rose\Arry
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

use Rose\Errors\ArgumentError;
use Rose\Errors\UndefinedPropertyError;

use Rose\Text;
use Rose\Regex;
use Rose\Map;
use Rose\Math;

/*
**	Generic container for objects. This class allows the items to be indexed using only a numeric index.
*/

class Arry
{
	/*
	**	The actual array contents (implemented as native array).
	*/
    public $__nativeArray;

	/*
	**	Constructs an instance of the class.
	*/
    public function __construct ($nativeArray=null, $recursiveScan=true)
    {
		$this->__nativeArray = $nativeArray ? array_values($nativeArray) : array();

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
	**	Builds a new array using a native array.
	*/
    public static function fromNativeArray ($nativeArray, $recursiveScan=true)
    {
		if ($nativeArray === null)
			return null;

        $newArray = new Arry ();
		$newArray->__nativeArray = array_values($nativeArray);

        if ($recursiveScan)
        {
            foreach ($newArray->__nativeArray as &$value)
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

        return $newArray;
    }

	/*
	**	Returns the length of the array.
	*/
    public function length ()
    {
        return sizeof($this->__nativeArray);
    }

	/*
	**	Definition of the get operator.
	*/
    public function get ($index)
    {
        if (!Math::inrange ($index, 0, sizeof($this->__nativeArray)-1))
            throw new ArgumentError ('Index Out of Bounds');

        return $this->__nativeArray[$index];
    }

	/*
	**	Definition of the set operator.
	*/
    public function set ($index, $value)
    {
        $this->__nativeArray[$index] = $value;
        return $value;
    }

	/*
	**	Sorts the array in ascending or descending order.
	*/
    public function sort ($order='ASC')
    {
        if ($order == 'ASC')
            asort ($this->__nativeArray);
        else
			arsort ($this->__nativeArray);

		$this->__nativeArray = array_values($this->__nativeArray);
        return $this;
    }

	/*
	**	Sorts the array by item length.
	*/
    public function sortl ($order='ASC')
    {
        if ($order == 'ASC')
            usort ($this->__nativeArray, array('Rose\Arry', '__sortl1'));
        else
			usort ($this->__nativeArray, array('Rose\Arry', '__sortl2'));

        return $this;
    }

    public static function __sortl1 ($a, $b) { return strlen($a) - strlen($b); }
    public static function __sortl2 ($a, $b) { return strlen($b) - strlen($a); }

	/*
	**	Sorts the array using the specified comparator function.
	*/
    public function sortx ($comparator_fn)
    {
        usort ($this->__nativeArray, $comparator_fn);
        return $this;
    }

	/*
	**	Returns the last item in the array, an offset can be specified.
	*/
    public function last ($offset=0)
    {
		return $this->get($this->length() - ($offset+1));
	}

	/*
	**	Returns the first item in the array, an offset can be specified.
	*/
    public function first ($offset=0)
    {
		return $this->get($offset);
    }

	/*
	**	Returns the  item at the specified index. Negative indices refer to the end of the array.
	*/
    public function at ($index)
    {
		return $index >= 0 ? $this->first($index) : $this->last(-$index-1);
    }

	/*
	**	Pushes an item to the bottom of the array.
	*/
    public function push ($value)
    {
        array_push ($this->__nativeArray, $value);
        return $this;
    }

	/*
	**	Pops an item off of the bottom of the array.
	*/
    public function pop ()
    {
        return array_pop ($this->__nativeArray);
    }

	/*
	**	Prepends the given value to the front of the array.
	*/
    public function unshift ($value)
    {
        array_unshift ($this->__nativeArray, $value);
        return $this;
    }

	/*
	**	Shifts an item off of the beginning of the array.
	*/
    public function shift ()
    {
        return array_shift ($this->__nativeArray);
    }

	/*
	**	Inserts an item to the bottom of the array.
	*/
    public function add ($value)
    {
        array_push ($this->__nativeArray, $value);
        return $this;
    }

	/*
	**	Removes an item from the array, returns the removed item or null if not found.
	*/
    public function remove ($index)
    {
        $item = $this->__nativeArray[$index];
		unset ($this->__nativeArray[$index]);

		$this->__nativeArray = array_values($this->__nativeArray);
        return $item;
    }

	/*
	**	Removes all the items that match the given pattern. Returns the array.
	*/
    public function removeAll ($pattern)
    {
        foreach ($this->__nativeArray as $key => $value)
        {
            if (Regex::_matches ($pattern, $value))
                unset ($this->__nativeArray[$key]);
		}

		$this->__nativeArray = array_values ($this->__nativeArray);
        return $this;
    }

	/*
	**	Selects all the items that match the given pattern. Returns new array.
	*/
    public function selectAll ($pattern)
    {
		$result = new Arry();

        foreach ($this->__nativeArray as $key => $value)
        {
            if (Regex::_matches ($pattern, $value))
                $result->push($value);
		}

        return $result;
    }

	/*
	**	Selects all the items that match the given pattern but returns a Map instead of an Arry.
	*/
    public function selectAllAsMap ($pattern)
    {
		$result = new Map();

        foreach ($this->__nativeArray as $key=>$value)
        {
            if (Regex::_matches ($pattern, $value))
                $result->set ($key, $value);
		}

        return $result;
    }

	/*
	**	Inserts an item at the given index, no item is overwritten the array is always expanded.
	*/
    public function insertAt ($index, $value)
    {
        if ($index < 0)
        {
            if (($index += $this->length()) < 0)
                $index = 0;
		}

        if ($index > $this->length())
			$index = $this->length();

        $this->__nativeArray = array_merge(array_slice($this->__nativeArray, 0, $index), array($value), array_slice($this->__nativeArray, $index));
        return $this;
    }

	/*
	**	Returns the index of the item whose value matches or null if not found, if the strict parameter is set, it will search for an identical item.
	*/
    public function indexOf ($value, $strict=false)
    {
        $result = array_search ($value, $this->__nativeArray, $strict);
        return ($result === false) ? null : $result;
    }

	/*
	**	Checks if the given index exists in the array.
	*/
    public function has ($index)
    {
        return array_key_exists($index, $this->__nativeArray) ? true : false;
    }

	/*
	**	Creates and returns a replica of the array.
	*/
    public function replicate ($deep=false)
    {
		$newArray = new Arry();
		$newArray->__nativeArray = $this->__nativeArray;

        if ($deep)
        {
            foreach ($newArray->__nativeArray as $key => $value)
            {
                if (typeOf($value) == 'Rose\\Arry' || typeOf($value) == 'Rose\\Map')
                    $newArray->set ($key, $value->replicate(true));
			}
		}

        return $newArray;
    }

	/*
	**	Returns a slice of the array, starting at the given index and reading the specified number of items, if the length is
	**	not specified the rest of items after the index (inclusive) will be returned.
	*/
    public function slice ($start, $length=null)
    {
        return new Arry (array_slice($this->__nativeArray, $start, $length), false);
    }

	/*
	**	Slices the array in blocks of the given size and returns an array with the resulting slices.
	*/
    public function slices ($size=16)
    {
		$result = new Arry();

		$n = $this->length();
        $i = null;

        for ($i = 0; $i < $n; $i += $size)
            $result->push ($this->slice($i, $size));

        return $result;
    }

	/*
	**	Returns a new array that is the result of merging the current array with the given array.
	*/
    public function merge ($array, $mergeInSelf=false)
    {
		if ($mergeInSelf === true)
		{
			$this->__nativeArray = array_merge ($this->__nativeArray, $array->__nativeArray);
			return $this;
		}

        return new Arry (array_merge($this->__nativeArray, $array->__nativeArray), false);
    }

	/*
	**	Removes all duplicate values from the current array and returns a new array.
	*/
    public function unique()
    {
        return new Arry (array_unique($this->__nativeArray), false);
    }

	/*
	**	Concatenates the contents of the given array to the current array.
	*/
    public function concat ($anArray)
    {
        $this->__nativeArray = array_merge($this->__nativeArray,$anArray->__nativeArray);
        return $this;
    }

	/*
	**	Returns a new array with the items order reversed.
	*/
    public function reverse()
    {
        return new Arry (array_reverse($this->__nativeArray), false);
    }

	/*
	**	Merges all the item in the array, returns a string.
	*/
    public function join ($separator='')
    {
        return \implode ($separator, $this->__nativeArray);
    }

	/*
	**	Maps each value of the array to another value using the specified filter function.
	*/
    public function map ($filter)
    {
		foreach ($this->__nativeArray as &$item)
		{
			$item = $filter($item);
		}

        return $this;
	}

	/*
	**	Returns a new array containing only elements that were accepted by the given filter function (by returning true).
	*/
    public function filter ($filter)
    {
		$tmp = new Arry();

		foreach ($this->__nativeArray as &$item)
		{
			if ($filter($item))
				$tmp->push($item);
		}

        return $tmp;
	}

	/*
	**	For each of the items in the array the specified function is called.
	*/
    public function forEach ($function)
    {
		foreach ($this->__nativeArray as $index => $item)
		{
			$function ($item, $index, $this);
		}

        return $this;
    }

	/*
	**	Calls Text.format with the given string and one parameter which is the value of each item in the array.
	*/
    public function format ($formatString, $useContents=false)
    {
		$result = new Arry();

        if ($useContents)
        {
            foreach ($this->__nativeArray as $item)
            {
                $result->push (Text::format ($formatString, $item));
            }
        }
        else
        {
            $temp = new Map (array('0'=>'', 'index'=>0), false);
            foreach ($this->__nativeArray as $index => $item)
            {
                $temp->{'0'} = $item;
                $temp->index = $index;
                $result->push (Text::format ($formatString, $temp));
			}
		}

        return $result;
    }

	/*
	**	Clears the contents of the array.
	*/
    public function clear ()
    {
        $this->__nativeArray = array();
        return $this;
    }

	/*
	**	Dynamic getter.
	*/
    public function __get ($name)
    {
        switch ($name)
        {
            case 'length':
				return sizeof($this->__nativeArray);
        }

		$name = (int)$name;

		if (!($this->has($name)))
			return null;

        return $this->get((int)$name);
    }

	/*
	**	Converts the array to its text representation and returns it. The typeName parameter is ignored.
	*/
	public function __toString ()
    {
		$s = array();

		foreach ($this->__nativeArray as $index => $item)
		{
			switch (typeOf($item, true))
			{
				case 'Rose\\Arry':
				case 'Rose\\Map':
					$s[] = (string)$item;
					break;

				case 'null':
					$s[] = 'null';
					break;

				case 'bool':
					$s[] = $item ? 'true' : 'false';
					break;

				case 'int':
				case 'number':
					$s[] = $item;
					break;

				case 'string':
					$s[] = '"' . addcslashes($item, "\"\\\f\n\r\v\t") . '"';
					break;

				default:
					$s[] = '"' . addcslashes((string)$item, "\"\\\f\n\r\v\t") . '"';
					break;
			}
		}

		return '[' . implode(", ", $s) . ']';
    }
};
