<?php

namespace Rose\Ext;

use Rose\Errors\Error;
use Rose\Map;
use Rose\Expr;

// @title Map

/**
 * Constructs a map with the given key-value pairs. Note that the second form (&) is legacy from previous syntax.
 * @code (`map:new` [key value...])
 * @code (`&` [key value...])
 * @code { key value... }
 * @example
 * (map:new 'a' 1 'b' 2)
 * ; {"a":1,"b":2}
 *
 * (& "name" "Jenny" "age" 25)
 * ; {"name":"Jenny","age":25}
 *
 * { name "Jon" age 36 }
 * ; {"name":"Jon","age":36}
 */
Expr::register('map:new', function($args) {
    $map = new Map();
    for ($i = 1; $i+1 < $args->length; $i += 2)
        $map->set($args->get($i), $args->get($i+1));
    return $map;
});

/**
 * Sorts the map in place by value in ascending order.
 * @code (`map:sort-asc` <map>)
 * @example
 * (map:sort-asc (map:new 'b' 2 'a' 1))
 * ; {"a": 1, "b": 2}
 */
Expr::register('map:sort-asc', function($args) {
    $map = $args->get(1);
    $map->sort('ASC');
    return $map;
});

/**
 * Sorts the map in place by value in descending order.
 * @code (`map:sort-desc` <map>)
 * @example
 * (map:sort-desc (map:new 'b' 2 'a' 1))
 * ; {"b": 2, "a": 1}
 */
Expr::register('map:sort-desc', function($args) {
    $map = $args->get(1);
    $map->sort('DESC');
    return $map;
});

/**
 * Sorts the map in place by key in ascending order.
 * @code (`map:ksort-asc` <map>)
 * @example
 * (map:ksort-asc (map:new 'b' 5 'a' 10))
 * ; {"a": 10, "b": 5}
 */
Expr::register('map:ksort-asc', function($args) {
    $map = $args->get(1);
    $map->ksort('ASC');
    return $map;
});

/**
 * Sorts the map in place by key in descending order.
 * @code (`map:ksort-desc` <map>)
 * @example
 * (map:ksort-desc (map:new 'b' 5 'a' 10))
 * ; {"b": 5, "a": 10}
 */
Expr::register('map:ksort-desc', function($args) {
    $map = $args->get(1);
    $map->ksort('DESC');
    return $map;
});

/**
 * Returns the keys of the map.
 * @code (`map:keys` <map>)
 * @example
 * (map:keys (map:new 'a' 1 'b' 2))
 * ; ["a", "b"]
 */
Expr::register('map:keys', function($args) {
    $map = $args->get(1);
    return $map->keys();
});

/**
 * Returns the values of the map.
 * @code (`map:values` <map>)
 * @example
 * (map:values (map:new 'a' 1 'b' 2))
 * ; [1, 2]
 */
Expr::register('map:values', function($args) {
    $map = $args->get(1);
    return $map->values();
});

/**
 * Sets one or more key-value pairs in the map.
 * @code (`map:set` <map> [key value...])
 * @example
 * (map:set (map:new 'a' 1) 'b' 2 'x' 15)
 * ; {"a": 1, "b": 2, "x": 15}
 */
Expr::register('map:set', function($args) {
    $map = $args->get(1);
    for ($i = 2; $i+1 < $args->length; $i+=2)
        $map->set($args->get($i), $args->get($i+1));
    return $map;
});

/**
 * Returns the value of the given key in the map.
 * @code (`map:get` <map> <key>)
 * @example
 * (map:get (map:new 'a' 1 'b' 2) 'b')
 * ; 2
 */
Expr::register('map:get', function($args) {
    $map = $args->get(1);
    return $map->{$args->get(2)};
});

/**
 * Returns `true` if the map has the given key, `false` otherwise.
 * @code (`map:has` <map> <key>)
 * @example
 * (map:has (map:new 'a' 1 'b' 2) 'b')
 * ; true
 */
Expr::register('map:has', function($args) {
    $map = $args->get(1);
    return $map->has($args->get(2));
});

/**
 * Removes the given key from the map and returns the removed value.
 * @code (`map:del` <map> <key>)
 * @example
 * (map:del (map:new 'a' 1 'b' 112) 'b')
 * ; 112
 */
Expr::register('map:del', function($args) {
    return $args->get(1)->remove((string)$args->get(2));
});

/**
 * Returns the key of the element whose value matches or `null` if not found.
 * @code (`map:key` <map> <value>)
 * @example
 * (map:key (map:new 'a' 1 'b' 2) 2)
 * ; b
 */
Expr::register('map:key', function($args) {
    return $args->get(1)->keyOf($args->get(2));
});

/**
 * Returns the length of the Map.
 * @code (`map:len` <map>)
 * @example
 * (map:length (map:new 'a' 1 'b' 2))
 * ; 2
 */
Expr::register('map:len', function($args) {
    return $args->get(1)->length();
});

/**
 * Merges one or more maps into the first.
 * @code (`map:assign` <output-map> <map...>)
 * @example
 * (map:assign (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
 * ; {"a": 1, "b": 2, "c": 3}
 */
Expr::register('map:assign', function($args) {
    $m = $args->get(1);
    for ($i = 2; $i < $args->length(); $i++)
        $m = $m->merge($args->get($i), true);
    return $m;
});

/**
 * Merges one or more maps into a new map.
 * @code (`map:merge` <map...>)
 * @example
 * (map:merge (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
 * ; {"a": 1, "b": 2, "c": 3}
 */
Expr::register('map:merge', function($args) {
    $m = $args->get(1);
    for ($i = 2; $i < $args->length(); $i++)
        $m = $m->merge($args->get($i));
    return $m;
});

/**
 * Clears the contents of the map.
 * @code (`map:clear` <map>)
 * @example
 * (map:clear (map:new 'a' 1 'b' 2))
 * ; {}
 */
Expr::register('map:clear', function($args) {
    return $args->get(1)->clear();
});

/**
 * Returns the difference between two maps.
 * @code (`map:diff` <map1> <map2>)
 * @example
 * (map:diff { a 1 b 2 } { a 2 b 2 c 3 })
 * ; {"a":[1,2], "c":[null,3]}
 */
Expr::register('map:diff', function($args) {
    return $args->get(1)->diff($args->get(2));
});
