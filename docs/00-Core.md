# Core

## `#` \<value...>
Constructs an array.
```lisp
(# 1 2 3)
; [1,2,3]
```

## `&` \<name> \<expr> [\<name> \<expr>]*
Constructs an object.
```lisp
(& name "John" last "Doe")
; {"name":"John","last":"Doe"}
```

## `global`
Contains a truly global object, available to be used across all contexes.
```lisp
(set global.name "John")
(echo "My name is (global.name)")
; My name is John
```

## `abs` \<value>
Returns the absolute value.
```lisp
(abs 3.14)
; 3.14

(abs -3.14)
; 3.14
```

## `round` \<value>
Returns the value rounded to the nearest integer.
```lisp
(round 3.14)
; 3

(round 3.75)
; 4
```

## `ceil` \<value>
Returns the value rounded up to the nearest integer.
```lisp
(ceil 3.14)
; 4

(ceil 3.75)
; 4
```

## `floor` \<value>
Returns the value rounded down to the nearest integer.
```lisp
(floor 3.14)
; 3

(floor 3.75)
; 3
```

## `and` \<value...>
Checks each value and returns the first **falsey** one found, or returns the last one in the sequence.
```lisp
(and 1 2 3)
; 3

(and 1 0 12)
; 0

(and true true true)
; true

(and false 12 2)
; false
```

## `or` \<value...>
Checks each value and returns the first **truthy** one found, or returns the last one in the sequence.
```lisp
(or 1 2 3)
; 1

(or 0 false "Hello" false)
; Hello
```

## `bit-not` \<value>
Return the result of a NOT (bitwise) operation.
```lisp
(bit-not 12)
; -13
```

## `bit-and` \<valueA> \<valueB>
Returns the result of an AND (bitwise) operation between `valueA` and `valueB`.
```lisp
(bit-and 7 29)
; 5
```

## `bit-or` \<valueA> \<valueB>
Returns the result of an OR (bitwise) operation between `valueA` and `valueB`.
```lisp
(bit-or 7 29)
; 31
```

## `bit-xor` \<valueA> \<valueB>
Returns the result of an XOR (bitwise) operation between `valueA` and `valueB`.
```lisp
(bit-xor 7 29)
; 26
```

## `coalesce` \<value...>
## `??` \<value...>
Checks each value and returns the first non-null found, or `null` if none found.
```lisp
(coalesce false 0 12)
; false

(coalesce 0 12)
; 0

(?? null null 5)
; 5

(coalesce null null)
; null
```

## `eq` \<value1> \<value2>
## `eq?` \<value1> \<value2>
Equals-operator. Checks if both values are the same using loose type checking, returns boolean.
```lisp
(eq 12 "12")
; true

(eq 0 false)
; true

(eq false null)
; true

(eq true 12)
; true

(eq 12 13)
; false
```

## `eqq` \<value1> \<value2>
## `eqq?` \<value1> \<value2>
Identical-operator. Checks if both values are identical (strong type checking), returns boolean.
```lisp
(eqq 12 "12")
; false

(eqq 0 false)
; false

(eqq false null)
; false

(eqq true 12)
; false

(eqq 12 12)
; true

(eqq "X" "X)
; true
```

## `ne` \<value1> \<value2>
## `ne?` \<value1> \<value2>
Not-equals operator. Checks if both values are different using loose type checking, returns boolean.
```lisp
(ne 12 "12")
; false

(ne 0 false)
; false

(ne false null)
; false

(ne true 12)
; false

(ne 12 13)
; true
```

## `starts-with` \<value> \<text>
Returns `true` if the text starts with the specified value.
```lisp
(starts-with "He" "Hello")
; true

(starts-with "he" "Hello")
; false
```

## `ends-with` \<value> \<text>
Returns `true` if the text ends with the specified value.
```lisp
(ends-with "lo" "Hello")
; true

(ends-with "Lo" "Hello")
; false
```

## `in?` \<subject> \<values...>
Returns `true` if the subject is contained in the list of values.
```lisp
(in? 12 1 2 3 4 5)
; false

(in? 12 1 2 3 4 5 12)
; true
```

## `lt` \<value1> \<value2>
## `lt?` \<value1> \<value2>
Less-than operator. Returns `true` if value1 < value2.
```lisp
(lt 1 2)
; true

(lt 10 10)
; false
```

## `le` \<value1> \<value2>
## `le?` \<value1> \<value2>
Less-than-or-equal operator. Returns `true` if value1 <= value2.
```lisp
(le 1 2)
; true

(le 10 10)
; true
```

## `gt` \<value1> \<value2>
## `gt?` \<value1> \<value2>
Greater-than operator. Returns `true` if value1 > value2.
```lisp
(gt 1 2)
; false

(gt 10 10)
; false
```

## `ge` \<value1> \<value2>
## `ge?` \<value1> \<value2>
Greater-than-or-equal operator. Returns `true` if value1 >= value2.
```lisp
(ge 1 2)
; false

(ge 10 10)
; true
```

## `isnotnull` \<value>
## `not-null?` \<value>
Returns `true` if the value is not `null` (identical comparison).
```lisp
(isnotnull 12)
; true

(isnotnull 0)
; true

(isnotnull false)
; true

(isnotnull null)
; false
```

## `isnull` \<value>
## `null?` \<value>
Returns `true` if the value is `null` (identical comparison).
```lisp
(isnull 12)
; false

(isnull 0)
; false

(isnull false)
; false

(isnull null)
; true
```

## `iszero` \<value>
## `zero?` \<value>
Returns `true` if the value is zero (loose comparison).
```lisp
(iszero 0)
; true

(iszero false)
; true

(iszero null)
; true

(iszero 1)
; false
```

## `even?` \<value>
Returns `true` if the value is an even number.
```lisp
(even? 12)
; true

(even? 13)
; false
```

## `odd?` \<value>
Returns `true` if the value is an odd number.
```lisp
(odd? 12)
; false

(odd? 13)
; true
```

## `int?` \<value>
Returns `true` if the value is an integer number.
```lisp
(int? 12)
; true

(int? 3.14)
; false
```

## `float?` \<value>
Returns `true` if the value is a floating-point number.
```lisp
(float? 12)
; false

(float? 3.14)
; true
```

## `bool?` \<value>
Returns `true` if the value is a boolean.
```lisp
(bool? true)
; true

(bool? 12)
; false
```

## `str?` \<value>
Returns `true` if the value is a string.
```lisp
(str? "Hello")
; true

(str? 12)
; false
```

## `array?` \<value>
Returns `true` if the value is an array.
```lisp
(array? (# 1 2 3))
; true

(array? (&))
; false
```

## `object?` \<value>
## `map?` \<value>
Returns `true` if the value is an object/map.
```lisp
(object? (& name "John Doe"))
; true

(object? 32)
; false
```

## `fn?` \<value>
Returns `true` if the value is a function.
```lisp
(fn? (fn n 0))
; true

(fn? 12.5)
; false
```

## `*` \<value...>
Multiplies the values and returns the result (number).
```lisp
(* 2 -1.5 3.25)
; -9.75
```

## `mul` \<value...>
Multiplies the values and returns the result (integer).
```lisp
(mul 2 -1.5 3.25)
; -9
```

## `/` \<value...>
Returns (number) the result of dividing each value by the next one.
```lisp
(/ 100 10 3)
; 3.333333333
```

## `div` \<value...>
Returns (integer) the result of dividing each value by the next one.
```lisp
(/ 100 10 3)
; 3
```

## `+` \<value...>
Returns the sum of all values.
```lisp
(+ 1 2 3)
; 6
```

## `-` \<value...>
Returns the result of subtracting each value by the next one.
```lisp
(- 10 5 -2)
; 7
```

## `mod` \<value...>
Returns the remainder of dividing each value by the next one.
```lisp
(mod 131 31 5)
; 2
```

## `pow` \<value...>
Returns the result of raising each number to the next one.
```lisp
(pow 3 2 4)
; 6561
```

## `min` \<value...>
Returns the minimum value in the sequence.
```lisp
(min 10 4 2 12)
; 2
```

## `max` \<value...>
Returns the maximum value in the sequence.
```lisp
(max 10 4 2 12)
; 12
```

## `each` [\<varname>] \<array-expr> \<block>
Returns an array constructed by evaluating the given block for each of the items in the array, the optional `varname` parameter (default `i`) indicates the name of the iterator variable.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(each (# 1 2 3) (* (i) 1.5))
; [1.5,3,4.5]
```

## `gather` [\<varname>] [from \<number>] [to \<number>] [times \<number>] [step \<number>] \<block>
Repeats the specified block the specified number of times and gathers the results to construct an array.
```lisp
(gather times 4 (i))
; [0,1,2,3]

(gather from 1 times 10 step 2 (i))
; [1,3,5,7,9,11,13,15,17,19]

(gather x from 4 to 6
    (pow 2 (x))
)
; [16,32,64]
```

## `repeat` [\<varname>] [from \<number>] [to \<number>] [times \<number>] [step \<number>] \<block>
Repeats the specified block the specified number of times.
```lisp
(repeat times 2
    (echo (i))
)
; 0
; 1

(repeat from 1 times 5 step 2
    (echo (i))
)
; 1
; 3
; 5
; 7
; 9

(repeat x from 4 to 6
    (echo (pow 2 (x)))
)
; 16
; 32
; 64
```

## `loop` \<block>
Repeats the specified block **infinitely** until a `break` is found.
```lisp
(loop
    (echo "Hello")
    (break)
)
; Hello
```

## `while` \<condition> \<block>
Repeats the specified block until the condition is falsey or a `break` is found.
```lisp
(set i 0)
(while (lt (i) 10)
    (when-not (zero? (i))
        (print ":"))

    (print (i))
    (inc i)
)
; 0:1:2:3:4:5:6:7:8:9
```

## `contains` \<expr> \<name...>
Returns `true` if the specified object contains **all** the specified keys. If it fails the global variable `err` will contain an error message.
```lisp
(set a (& name "John"))

(if (not (contains (a) name last))
    (throw "Missing field: (err)"))

; Missing field: last
```

## `has` \<key> \<object-expr>
## `has` \<value> \<array-expr>
Returns `true` if the object has the specified key, or if the array has the specified value.
```lisp
(has name (& name "Red"))
; true

(has 3 (# A B C))
; false

(has 2 (# A B C))
; true
```


## `map` [\<varname>] \<array-expr> \<block>
Transforms each value in the array/map by executing the block and returns a new array/map.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(map (# 1 2 3) (* (i) 1.5))
; [1.5,3,4.5]

(map x (# 1 2 3) (pow 2 (x)))
; [2,4,8]
```

## `filter` [\<varname>] \<array-expr> \<block>
Returns a new array/map with the values that pass the test implemented by the block.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(filter (# 1 2 3 4 5 6 7 8 9 10) (even? (i)))
; [2,4,6,8,10]

(filter x (# 1 2 3 4 5 6 7 8 9 10) (odd? (x)))
; [1,3,5,7,9]
```

## `every` [\<varname>] \<array-expr> \<block>
Returns `true` if all the values in the array/map pass the test implemented by the block.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(every (# 1 2 3 4 5) (even? (i)))
; false

(every (# 1 2 3 4 5) (le? (i) 5))
; true
```

## `some` [\<varname>] \<array-expr> \<block>
Returns `true` if at least one of the values in the array/map pass the test implemented by the block.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(some (# 1 2 3 4 5) (even? (i)))
; true

(some (# 1 2 3 4 5) (gt? (i) 5))
; false
```

## `find` [\<varname>] \<array-expr> \<block>
Returns the first value in the array/map that passes the test implemented by the block or `null` if none found.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(find (# 1 2 3 4 5) (even? (i)))
; 2

(find (# 1 2 3 4 5) (gt? (i) 5))
; null
```

## `findIndex` [\<varname>] \<array-expr> \<block>
Returns the index of the first value in the array/map that passes the test implemented by the block or `null` if none found.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(findIndex (# 1 2 3 4 5) (even? (i)))
; 1

(findIndex (# 1 2 3 4 5) (gt? (i) 5))
; null
```

## `select` [\<varname>] \<condition> \<array-expr>
Returns a new array/map with the values that pass the condition. Similar to `find` with the difference that the condition is the second parameter.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(select (even? (i)) (# 1 2 3 4 5 6 7 8 9 10))
; [2,4,6,8,10]

(select x (odd? (x)) (# 1 2 3 4 5 6 7 8 9 10))
; [1,3,5,7,9]
```

## `pipe` \<expression...>
Executes one or more expressions. Makes the result of each expression available to the next via the internal `_` variable.

```lisp
(pipe
    10
    (+ 2 _)
    (pow _ 2)
)
; 144
```

## `expand` \<template> \<data>
Expands the specified template string (or already parsed template array) using the given data. The result will always be a string. The s-expression enclosing symbols will be '{' and '}' respectively instead of the usual parenthesis. If no data is provided, the current context will be used.

```lisp
(expand "Hello {name}!" (& name "John"))
; Hello John!

(expand "Hello {upper {name}}!" (& name "Jane"))
; Hello JANE!
```

## `yield` \<level> \<value>
## `yield` [\<value>]
Yields a value to an inner block at a desired level to force the result to be the specified value, thus efectively exiting all the way to that block. If no level is specified, the current block will be used.

<small>Note: The inner-most block is level-1.</small>

```lisp
(echo
    (block ;@3
        (echo "@3-start")
        (block ;@2
            (echo "@2-start")
            (block ;@1
                (echo "@1-start")
                (yield 3 "Value-3")
                (echo "@1-end")
            )
            (echo "@2-end")
            "Value-2"
        )
        (echo "@3-end")
        "Value-1"
    )
)
; @3-start
; @2-start
; @1-start
; Value-3
```

## `exit` \<level>
Yields a `null` value to an inner block at a desired level, efectively exiting all the way to that block. If no level is specified, the current block will be used.

<small>Note: The inner-most block is level-1.</small>

```lisp
(echo (dump
    (block ;@3
        (echo "@3-start")
        (block ;@2
            (echo "@2-start")
            (exit 2)
            (echo "@2-end")
        )
        (echo "@3-end")
        "Value-1"
    )
))
; @3-start
; @2-start
; null
```

## `with` \<varname> \<value> \<block>
Introduces a new temporal variable with the specified value to be used in the block, the variable will be returned to its original state (or removed) once the `with` block is completed. Returns the value returned by the block.

```lisp
(with a 12
    (echo (a))
)
(echo (a))
; 12
; Error: Function `a` not found.
```

## `fn` [\<param...>] \<block>
Creates a function with the specified parameters and function body block. Returns the function object.

```lisp
(set X (fn a b
    (+ (a) (b))
))

((X) 5 7)
; 12
```

## `def-fn` [private|public] \<name> [\<param...>] \<block>
Defines a function with the specified name, parameters and body block.

Functions are isolated and do not have access to any of the outer scopes (except definitions), however internal variables `local` can be used to access to current scope (where the function is defined), and `global` to access the global scope.

```lisp
(set a 12)

(def-fn add_value x
    (+ (x) (a)))

(def-fn add_value_2 x
    (+ (x) (local.a)))

(add_value 10)
; Error: Function `a` not found.

(add_value_2 10)
; 22
```

## `ret` [\<value>]
Returns from a function with the specified value (or `null` if none specified).

```lisp
(def-fn getval
    (ret 3)
)
(getval)
; 3
```

## `def` [public|private] \<varname> \<value>
Defines a constant variable in the current scope. The variable can only be changed by overriding it with another `def`.
```lisp
(def a "Hello")

(def-fn x
    (str (a) " World"))

(x)
; Hello World
```

## `ns` [public|private] [\<name>]
Sets the active namespace for any `def-*` statements.
```lisp
(ns math)
(def PI 3.141592)

(echo (math::PI))
; 3.141592
```

## `include` \<source-path...>
Includes one or more source files and evaluates them, as if they were written in the current source file.

```lisp
(include "lib/math.fn")
(echo (math::PI))
; 3.141592
```

## `import` \<source-path> [`as` \<namespace-name>]
Imports definitions from a source file into a namespace, or the current namespace if none specified.

```lisp
(include "lib/math" as "m")
(echo (m::PI))
; 3.141592
```

## `zipmap` \<key-name...> \<array-expr>
## `zipmap` \<array-expr> \<array-expr>
Creates a new map by zipping the respective keys and values together.

```lisp
(zipmap (# a b c) (# 1 2 3))
; {"a":1,"b":2,"c":3}

(zipmap "a" "b" "c" (# 10 20 30))
; {"a":10,"b":20,"c":30}
```

## `map-get` \<key-name...> \<object-expr>
## `map-get` <\array-expr> \<object-expr>
Extracts the specified keys from a given map and returns a new map.

```lisp
(map-get (# a b d) (& a 1 b 2 c 3 d 4))
; {"a":1,"b":2,"d":4}

(map-get "a" "c" (& a 1 b 2 c 3 d 4))
; {"a":1,"c":3}
```

## `mapify` [\<varname>] \<array-expr> \<key-expr> [\<value-expr>]
Returns a new map created with the specified key-expression and value-expression.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(mapify i (# 1 2 3) (str "K" (i)) (pow 2 (i)))
; {"K1":2,"K2":4,"K3":8}

(mapify i (# 1 2 3) (i))
; {"1":1,"2":2,"3":3}
```

## `groupify` [\<varname>] \<array-expr> \<key-expr> [\<value-expr>]
Returns a new map created by grouping all values having the same key-expression.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(groupify i (# 1 2 3 4 5 6 7 8 9 10) (mod (i) 3))
; {"1":[1,4,7,10],"2":[2,5,8],"0":[3,6,9]}

(groupify i (# 1 2 3 4) (mod (i) 2) (* 3 (i)))
; {"1":[3,9],"0":[6,12]}
```

## `eval` \<template> \<data>
Evaluates the specified template string (or already parsed template array) using the given data. The s-expression enclosing symbols will be '{' and '}' respectively instead of the usual parenthesis. If no data is provided, the current context will be used.
```lisp
(eval "{eq 1 1}")
; true

(eval "{str {upper {name}} ' ' Doe}" (& name "John"))
; JOHN Doe
```
