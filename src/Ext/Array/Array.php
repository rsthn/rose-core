<?php

namespace Rose\Ext;

use Rose\Errors\Error;
use Rose\Arry;
use Rose\Expr;

// @title Array

/**
 * Constructs an array. Note that the second form (#) is legacy from previous syntax.
 * @code (`array` [values...])
 * @code (`#` [values...])
 * @code [ values... ]
 * @example
 * (array 1 2 3)
 * ; [1,2,3]
 *
 * (# 1 2 3)
 * ; [1,2,3]
 *
 * [1 2 3]
 * ; [1,2,3]
 */
Expr::register('array', function($args) {
    $array = new Arry();
    for ($i = 1; $i < $args->length; $i++)
        $array->push($args->get($i));
    return $array;
});

/**
 * array:sort [<a-var>] [<b-var>] <array-expr> <block>
 */

/**
 * Sorts the array in place using a custom comparison function.
 * @code (`array:sort` <var-a> <var-b> <array> <block>)
 * @example
 * (array:sort a b (array 3 1 2) (- (a) (b)) )
 * ; [1, 2, 3]
 */
Expr::register('_array:sort', function($parts, $data)
{
    $a_name = 'a';
    $b_name = 'b';
    $i = 1;

    Expr::takeIdentifier($parts, $data, $i, $a_name);
    Expr::takeIdentifier($parts, $data, $i, $b_name);

    $list = Expr::expand($parts->get($i++), $data, 'arg');
    if (!$list || \Rose\typeOf($list) != 'Rose\Arry')
        throw new \Rose\Errors\Error('(array:sort) invalid array expression');

    $block = $parts->slice($i);

    $a_present = false; $a_value = null;
    if ($data->has($a_name)) {
        $a_present = true;
        $a_value = $data->get($a_name);
    }

    $b_present = false; $b_value = null;
    if ($data->has($b_name)) {
        $b_present = true;
        $b_value = $data->get($b_name);
    }

    $list->sortx(function($a, $b) use(&$a_name, &$b_name, &$data, &$block) {
        $data->set($a_name, $a);
        $data->set($b_name, $b);
        return Expr::blockValue($block, $data);
    });

    if ($a_present) $data->set($a_name, $a_value);
    else $data->remove($a_name);

    if ($b_present) $data->set($b_name, $b_value);
    else $data->remove($b_name);

    return $list;
});

/**
 * Sorts the array in place in ascending order.
 * @code (`array:sort-asc` <array>)
 * @example
 * (array:sort-asc (array 3 1 2))
 * ; [1, 2, 3]
 */
Expr::register('array:sort-asc', function($args) {
    $array = $args->get(1);
    $array->sort('ASC');
    return $array;
});

/**
 * Sorts the array in place in descending order.
 * @code (`array:sort-desc` <array>)
 * @example
 * (array:sort-desc (array 3 1 2 15 -6 7))
 * ; [15, 7, 3, 2, 1, -6]
 */
Expr::register('array:sort-desc', function($args) {
    $array = $args->get(1);
    $array->sort('DESC');
    return $array;
});

/**
 * Sorts the array in place by the length of its elements in ascending order.
 * @code (`array:lsort-asc` <array>)
 * @example
 * (array:lsort-asc (array "fooo" "barsss" "baz" "qx"))
 * ; ["qx", "baz", "fooo", "barsss"]
 */
Expr::register('array:lsort-asc', function($args) {
    $array = $args->get(1);
    $array->lsort('ASC');
    return $array;
});

/**
 * Sorts the array in place by the length of its elements in descending order.
 * @code (`array:lsort-desc` <array>)
 * @example
 * (array:lsort-desc (array "fooo" "barsss" "baz" "qx"))
 * ; ["barsss", "fooo", "baz", "qx"]
 */
Expr::register('array:lsort-desc', function($args)
{
    $array = $args->get(1);
    $array->lsort('DESC');
    return $array;
});

/**
 * Adds one or more values to the end of the array.
 * @code (`array:push` <array> <value...>)
 * @example
 * (array:push (array 1 2) 3 4)
 * ; [1, 2, 3, 4]
 */
Expr::register('array:push', function($args) {
    $array = $args->get(1);
    for ($i = 2; $i < $args->length; $i++)
        $array->push($args->get($i));
    return $array;
});

/**
 * Adds one or more values to the beginning of the array.
 * @code (`array:unshift` <array> <value...>)
 * @example
 * (array:unshift (array 1 2) 3 4)
 * ; [3, 4, 1, 2]
 */
Expr::register('array:unshift', function($args) {
    $array = $args->get(1);
    for ($i = $args->length-1; $i >= 2; $i--)
        $array->unshift($args->get($i));
    return $array;
});

/**
 * Removes the last element from the array and returns it.
 * @code (`array:pop` <array>)
 * @example
 * (array:pop (array 1 2 3))
 * ; 3
 */
Expr::register('array:pop', function($args) {
    return $args->get(1)->pop();
});

/**
 * Removes the first element from the array and returns it.
 * @code (`array:shift` <array>)
 * @example
 * (array:shift (array 1 2 3))
 * ; 1
 */
Expr::register('array:shift', function($args) {
    return $args->get(1)->shift();
});

/**
 * Returns the first element of the array or `null` if the array is empty.
 * @code (`array:first` <array>)
 * @example
 * (array:first (array 1 2 3))
 * ; 1
 */
Expr::register('array:first', function($args) {
    return $args->get(1)->first();
});

/**
 * Returns the last element of the array or `null` if the array is empty.
 * @code (`array:last` <array>)
 * @example
 * (array:last (array 1 2 3))
 * ; 3
 */
Expr::register('array:last', function($args) {
    return $args->get(1)->last();
});

/**
 * Removes the item from the array at a given index and returns it, throws an error if the index is out of bounds.
 * @code (`array:remove` <array> <index>)
 * @example
 * (array:remove (array 1 2 3) 1)
 * ; 2
 * (array:remove (array 1 2 3) 3)
 * ; Error: Index out of bounds: 3
 */
Expr::register('array:remove', function($args) {
    return $args->get(1)->remove((int)$args->get(2));
});

/**
 * Returns the index of the item whose value matches or `null` if not found.
 * @code (`array:index` <array> <value>)
 * @example
 * (array:index (array 1 2 3) 2)
 * ; 1
 * (array:index (array 1 2 3) 4)
 * ; null
 */
Expr::register('array:index', function($args) {
    return $args->get(1)->indexOf($args->get(2));
});

/**
 * Returns the last index of the item whose value matches or `null` if not found.
 * @code (`array:last-index` <array> <value>)
 * @example
 * (array:last-index (array 1 2 3 2) 2)
 * ; 3
 */
Expr::register('array:last-index', function($args) {
    return $args->get(1)->lastIndexOf($args->get(2));
});

/**
 * Returns the length of the array.
 * @code (`array:length` <array>)
 * @example
 * (array:length (array 1 2 3))
 * ; 3
 */
// TODO: Possibly useless since we have (len X) already.
Expr::register('array:length', function($args) {
    return $args->get(1)->length();
});

/**
 * Appends the contents of the given arrays, the original array will be modified.
 * @code (`array:append` <array> <array>)
 * @example
 * (array:append (array 1 2) (array 3 4))
 * ; [1, 2, 3, 4]
 */
Expr::register('array:append', function($args) {
    return $args->get(1)->append($args->get(2));
});

/**
 * Returns a **new** array as the result of merging the given arrays.
 * @code (`array:merge` <array> <array>)
 * @example
 * (array:merge (array 1 2) (array 3 4))
 * ; [1, 2, 3, 4]
 */
Expr::register('array:merge', function($args) {
    return $args->get(1)->merge($args->get(2));
});

/**
 * Removes all duplicate values from the array and returns a new array.
 * @code (`array:unique` <array>)
 * @example
 * (array:unique (array 1 2 2 3 3 3))
 * ; [1, 2, 3]
 */
Expr::register('array:unique', function($args) {
    return $args->get(1)->unique();
});

/**
 * Returns a new array with the items in reverse order.
 * @code (`array:reverse` <array>)
 * @example
 * (array:reverse (array 1 2 3))
 * ; [3, 2, 1]
 */
Expr::register('array:reverse', function($args) {
    return $args->get(1)->reverse();
});

/**
 * Clears the contents of the array.
 * @code (`array:clear` <array>)
 * @example
 * (array:clear (array 1 2 3))
 * ; []
 */
Expr::register('array:clear', function($args)
{
    return $args->get(1)->clear();
});

/**
 * Creates and returns a replica of the array.
 * @code (`array:clone` <array> [deep=false])
 * @example
 * (array:clone (array 1 2 3))
 * ; [1, 2, 3]
 */
Expr::register('array:clone', function($args) {
    return $args->get(1)->replicate($args->{2} ?? false);
});

/**
 * 	array:flatten <depth> <array>
 * 	array:flatten <array>
 */

/**
 * Returns a flattened array up to the specified depth.
 * @code (`array:flatten` [depth] <array>)
 * @example
 * (array:flatten (array 1 2 (array 3 4) 5))
 * ; [1, 2, 3, 4, 5]
 * 
 * (array:flatten 1 (array 1 2 (array 3 (array 4 5 6)) 7))
 * ; [1, 2, 3, [4, 5, 6], 7]
 */
Expr::register('array:flatten', function($args)
{
    if ($args->length == 3)
        return $args->get(2)->flatten($args->get(1));
    else
        return $args->get(1)->flatten();
});

/**
 * Returns a slice of the array, starting at the given index and reading the specified number of items,
 * if the length is not specified the rest of items after the index (inclusive) will be returned.
 * @code (`array:slice` <start> [length] <array>)
 * @example
 * (array:slice 1 2 (array 1 2 3 4 5))
 * ; [2, 3]
 * 
 * (array:slice 2 (array 1 2 3 4 5))
 * ; [3, 4, 5]
 * 
 * (array:slice -3 2 (array 1 2 3 4 5))
 * ; [3, 4]
 *
 * (array:slice 1 -1 (array 1 2 3 4 5))
 * ; [2, 3, 4]
 */
Expr::register('array:slice', function($args)
{
    if ($args->length == 4)
        return $args->get(3)->slice($args->get(1), $args->get(2));
    else
        return $args->get(2)->slice($args->get(1));
});
