<?php

namespace Rose;

use Rose\Errors\ArgumentError;
use Rose\Errors\UndefinedPropertyError;

use Rose\Text;
use Rose\Regex;
use Rose\Map;
use Rose\Math;
use Rose\JSON;

/*
**	Generic container for objects. This class allows the items to be indexed using only numeric indices.
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
            if (\Rose\isArray($value))
            {
                if (count(array_filter(array_keys($value), '\Rose\isString')) != 0)
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
                if (\Rose\isArray($value))
                {
                    if (count(array_filter(array_keys($value), '\Rose\isString')) != 0)
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
        if (!\Rose\isNumeric($index))
            return null;

        if (!Math::inrange ((int)$index, 0, sizeof($this->__nativeArray)-1))
            throw new ArgumentError ('Index Out of Bounds: ' . $index);

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

    public static function __sortl1 ($a, $b) { return Text::length($a) - Text::length($b); }
    public static function __sortl2 ($a, $b) { return Text::length($b) - Text::length($a); }

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
        return $this->length() > 0 ? $this->get($this->length() - ($offset+1)) : null;
    }

    /*
    **	Returns the first item in the array, an offset can be specified.
    */
    public function first ($offset=0)
    {
        return $this->length() > 0 ? $this->get($offset) : null;
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
            if (($index += $this->length()+1) < 0)
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
    **	Returns the index of the last item whose value matches or null if not found, if the strict parameter is set, it will search for an identical item.
    */
    public function lastIndexOf ($value, $strict=false)
    {
        $n = $this->length();

        for ($i = $n-1; $i >= 0; $i--)
        {
            if ($strict)
            {
                if ($this->__nativeArray[$i] === $value)
                    return $i;
            }
            else
            {
                if ($this->__nativeArray[$i] == $value)
                    return $i;
            }
        }

        return null;
    }

    /*
    **	Checks if the given index exists in the array.
    */
    public function has ($index, $direct=false)
    {
        if ($index != null && \Rose\isString($index) && $direct === false)
        {
            if ($index[0] == '#' || $index == 'length')
                return true;

            if ($index[0] == '@')
                return $this->has(Text::substring($index, 1), true);
        }

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
    **	Appends the contents of the given array to the current array.
    */
    public function append ($array)
    {
        $this->__nativeArray = array_merge($this->__nativeArray, $array->__nativeArray);
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
        $tmp = new Arry();

        foreach ($this->__nativeArray as $item)
            $tmp->push($filter($item));

        return $tmp;
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
    **	Tests whether all elements in the array pass the test implemented by the provided function. Returns a boolean value.
    */
    public function every ($filter)
    {
        foreach ($this->__nativeArray as $item)
        {
            if (!$filter($item))
                return false;
        }

        return true;
    }

    /*
    **	Tests whether at least one element in the array passes the test implemented by the provided function. Returns boolean.
    */
    public function some ($filter)
    {
        foreach ($this->__nativeArray as $item)
        {
            if ($filter($item))
                return true;
        }

        return false;
    }

    /*
    **	Returns the first element that passes the test function or null if not found.
    */
    public function find ($filter)
    {
        foreach ($this->__nativeArray as $item)
        {
            if ($filter($item))
                return $item;
        }

        return null;
    }

    /*
    **	Returns the index of the first element that passes the test function or null if not found.
    */
    public function findIndex ($filter)
    {
        foreach ($this->__nativeArray as $index => $item)
        {
            if ($filter($item))
                return $index;
        }

        return null;
    }

    /*
    **	Returns a flattened array up to the specified depth.
    */
    public function flatten ($depth=1, $output=null)
    {
        if (!$output)
            $output = new Arry();

        foreach ($this->__nativeArray as $index => $item)
        {
            if (typeOf($item) === 'Rose\\Arry' && $depth > 0)
            {
                $item->flatten($depth-1, $output);
                continue;
            }

            $output->push($item);
        }

        return $output;
    }

    /*
    **	For each of the items in the array the specified function is called.
    */
    public function forEach ($function)
    {
        foreach ($this->__nativeArray as $index => &$item)
        {
            if ($function ($item, $index, $this) === false)
                break;
        }

        return $this;
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
        if (\Rose\isString($name))
        {
            switch ($name)
            {
                case 'length':
                    return sizeof($this->__nativeArray);

                default:
                    if ($name[0] == '#')
                        return $this->has(Text::substring($name, 1));

                    if ($name[0] == '@')
                        return $this->get(Text::substring($name, 1));
            }
        }

        //$name = (int)$name;

        if (!($this->has($name)))
            return null;

        return $this->get((int)$name);
    }

    /*
    **	Definition of the global setter for items.
    */
    public function __set ($name, $value)
    {
        $this->set((int)$name, $value);
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
                    $s[] = JSON::stringify($item);
                    break;

                case 'function':
                    $s[] = '(function)';
                    break;
    
                default:
                    $s[] = JSON::stringify((string)$item);
                    break;
            }
        }

        return '[' . \implode(',', $s) . ']';
    }
};
