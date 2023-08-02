
# Built-In Functions

- [Core](#core)
- [Locale](#locale)
- [Database](#database)
- [DateTime](#datetime)
- [Image](#image)
- [Path](#path)
- [File](#file)
- [Directory](#directory)
- [HTTP](#http)
- [Session](#session)
- [Utils](#utils)
- [Array](#array)
- [Map](#map)
- [Regular Expressions](#regular-expressions)
- [Wind](#wind)

<br/>

# Extensions

- [Sentinel (Extension)](#sentinel)
- [Shield (Extension)](#shield)

<br/><br/><br/>

# Core

## `echo` \<value...>
Writes the specified values to standard output and adds a new-line at the end.
```lisp
(echo "Hello" " " "World")
(echo "!")
; Hello World
; !
```

## `print` \<value...>
Writes the specified values to standard output.
```lisp
(print "Hello" "World" "!")
(print "!")
; HelloWorld!!
```

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

## `len` \<value>
Returns the number of elements (if Array or Map), or the length of the value (if a primitive type) or the number of bytes (if a string).
```lisp
(len (# 1 2 3))
; 3

(len hello)
; 5

(len "nice")
; 4

(len 202121)
; 6
```

## `strlen` \<value>
Returns the number of **characters** in a string. Assumes encoding is UTF-8.
```lisp
(strlen "Hello")
; 5

(strlen "Привет!")
; 7

```

## `int` \<value>
Converts the given value to an integer.
```lisp
(int 4.25)
; 4
```

## `bool` \<value>
Converts the given value to pure boolean.
```lisp
(bool 0)
; false
```

## `str` \<value...>
Converts all specified values to string and concatenates them.
```lisp
(str "Today is " 18 "th")
; Today is 18th
```

## `float` \<value>
Converts the given value to pure numeric value.
```lisp
(float "4.25")
; 4.25
```

## `chr` \<value>
Returns the character corresponding to the given ASCII value.
```lisp
(chr 64)
; @
```

## `ord` \<value>
Returns the value corresponding to the given ASCII character.
```lisp
(ord "K")
; 75
```

## `not` \<value>
Returns the logical NOT of the value.
```lisp
(not 8)
; false

(not false)
; true

(not 0)
; true
```

## `neg` \<value>
Negates the value.
```lisp
(neg 12)
; -12
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

## `typeof` \<value>
Returns a string with the type-name of the value. Possible values are: `null`, `string`, `bool`, `array`, `object`, `int`, `number`, and `function`.
```lisp
(typeof 12)
; int

(typeof 3.14)
; number

(typeof today)
; string

(typeof null)
; null

(typeof (# 1 2 3))
; array

(typeof (& value "Yes"))
; object

(typeof (fn n 0))
; function
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

## `void` \<block>
Executes the block and returns `null`.
```lisp
(void (+ 1 2 3))
; null
```

## `nop` \<block>
Prevents execution of the block and returns `null`.
```lisp
(nop (+ 1 2 3) (echo "Good day"))
; null
```

## `dump` \<value>
Dumps the value into readable string format.
```lisp
(dump 12)
; 12

(dump true)
; true

(dump (# 1 2 3))
; [1,2,3]
```

## `set` \<var-name> \<value>
Sets the value of one or more variables in the context.
```lisp
(set a 12)
(echo (a))
; 12
```

## `inc` \<var-name>
Increments the value of a variable.
```lisp
(set a 0)
(inc a)
; 1
```

## `dec` \<var-name>
Decrements the value of a variable.
```lisp
(set a 0)
(dec a)
; -1
```

## `append` \<var-name> \<value>
Appends the value to a variable.
```lisp
(append a "Hello")
(append a "World")
(a)
; HelloWorld
```

## `unset` \<var-name...>
Removes one or more variables from the context.
```lisp
(set a 12)
(a)
; 12

(unset a)
(a)
; Error: Function `a` not found.
```

## `trim` \<value...>
Returns the value without white-space on the left or right. The value can be a string, sequence or an array.
```lisp
(trim " Hello " " World ")
; ["Hello","World"]

(trim " Nice ")
; Nice
```

## `upper` \<value...>
Returns the value in uppercase. The value can be a string, sequence or an array.
```lisp
(upper "Hello" "World")
; ["HELLO","WORLD"]

(upper "Nice")
; NICE
```

## `lower` \<value...>
Returns the value in lower. The value can be a string, sequence or an array.
```lisp
(lower "Hello" "World")
; ["hello","world"]

(lower "Nice")
; nice
```

## `substr` \<start> \<count> \<string>
## `substr` \<start> \<string>
Returns a sub-string of the given value.
```lisp
(substr 0 2 "Hello")
; He

(substr 0 -1 "Hello")
; Hell

(substr -2 "Hello")
; lo

(substr -4 1 "Hello")
; e
```

## `replace` \<search> \<replacement> \<value...>
Replaces all occurences of `search` with `replacement` in the given value. The value can be a string, sequence or an array.
```lisp
(replace "l" "w" "Hello")
; Hewwo

(replace "l" "w" "Hello" "World")
; ["Hewwo","Worwd"]

(replace "l" "w" (# "Hello" "World"))
; ["Hewwo","Worwd"]
```

## `str::indexOf` \<search> \<text>
Returns the index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str::indexOf "l" "Hello")
; 2

(str::indexOf "l" "World")
; 3

(str::indexOf "x" "World")
; -1
```

## `str::lastIndexOf` \<search> \<text>
Returns the last index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str::lastIndexOf "l" "Hello")
; 3

(str::lastIndexOf "o" "Wwwwoorld")
; 5

(str::lastIndexOf "x" "World")
; -1
```

## `nl2br` \<value...>
Converts all new-line chars in the value to `<br/>`, the value can be a string, sequence or an array.
```lisp
(nl2br "Hello\nWorld")
; Hello<br/>World
```

## `%` \<tag-name> \<args>
Returns a string with the value inside an XML tag named `tag-name`, the value can be a string, sequence or an array.
```lisp
(% b hello world)
; <b>hello</b><b>world</b>
```

## `%%` \<tag-name> [\<attr> \<value>]* [\<value>]
Returns a string with the value inside an XML tag named `tag-name`, each pair of attr-value will become attributes of the tag and the last value will be treated as the tag contents.
```lisp
(%% b class red "Hello World")
; <b class="red">Hello World</b>
```

## `join` \<glue> \<array-expr>
## `join` \<array-expr>
Joins the array into a string. If `glue` is provided, it will be used as separator.
```lisp
(join (# a b c))
; abc

(join _ (# a b c))
; a_b_c
```

## `split` \<delimiter> \<str-expr>
## `split` \<str-expr>
Splits the string by the specified delimiter (or empty string if none specified). Returns an array.
```lisp
(split "," "A,B,C")
; ["A","B","C"]
```

## `keys` \<object-expr>
Returns an array with the keys of the object.
```lisp
(keys (& name "John" last "Doe"))
; ["name","last"]
```

## `values` \<object-expr>
Returns an array with the values of the object.
```lisp
(values (& name "John" last "Doe"))
; ["John","Doe"]
```

## `for` [\<varname>] \<array-expr> \<block>
Evaluates the given block for each of the items in the array and returns the **original array**, the optional `varname` parameter (default `i`) indicates the name of the iterator variable.

<small>NOTE: Extra variables `i#` and `i##` (iterator variable with suffix `#` and `##`) are automatically introduced to denote the index/key and numeric index of the current item respectively, note that the later will always have a numeric value.</small>

```lisp
(for (# 1 2 3) (* (i) 1.5))
; [1,2,3]
```

## `each` [\<varname>] \<array-expr> \<block>
Returns an array constructed by evaluating the given block for each of the items in the array, the optional `varname` parameter (default `i`) indicates the name of the iterator variable.

<small>NOTE: Just as in `for` the `i#` and `i##` variables will be available.</small>

```lisp
(each (# 1 2 3) (* (i) 1.5))
; [1.5,3,4.5]
```

## `?` \<expr> \<valueA> [\<valueB>]
Returns `valueA` if the expression is `true` otherwise returns `valueB` (or empty string if valueB was not specified). This is a short version of the `if` function.
```lisp
(? true "Yes" "No")
; Yes

(? false "Yes")
; (empty-string)
```

## `_if`
```lisp
```

## `when` \<condition> \<block>
Returns the value returned by the block if the expression is truthy.
```lisp
(when (eq 12 12) "Ok")
; Ok
```

## `when-not` \<condition> \<block>
Returns the value returned by the block if the expression is falsey.
```lisp
(when-not (eq 12 13) "Not Equal")
; Not Equal
```

## `switch` \<expr> [case <value> \<block> ...] [default \<block>]
Compares the result of the given expression against one of the case values (loose comparison). Executes the respective case block, or the `default` block if none matches.

```lisp
(set day 3)
(switch (day)
    case 1 "Monday"
    case 2 "Tuesday"
    case 3 "Wednesday"
    case 4 "Thursday"
    case 5 "Friday"
    case 6 "Saturday"
    case 7 "Sunday"
    default "Unknown"
)
; Wednesday
```

## `case` \<expr> [<value> \<result> ...] [\<default-result>]
Compares the result of the given expression against one of the case values (loose comparison). Returns the respective result or the default result if none matches. If no default result is specified, empty string is returned.

Note: This is meant for values, not blocks.
```lisp
(set day 8)
(case (day)
    1 "Monday" 		2 "Tuesday"		3 "Wednesday"	4 "Thursday"	
    5 "Friday"		6 "Saturday"	7 "Sunday"
    "Unknown"
)
; Unknown
```

## `break`
Exits the current inner most loop.
```lisp
(for i (# 1 2 3 4 5 6 7 8 9 10)
    (echo (i))
    (break)
)
; 1
```

## `continue`
Skips execution and continues the next iteration of the current inner most loop.
```lisp
(for i (# 1 2 3 4 5 6 7 8 9 10)
    (when (odd? (i))
        (continue))
    (echo (i))
)
; 2 4 6 8 10
```

## `repeat` [\<varname>] [from \<number>] [to \<number>] [times \<number>] [step \<number>] \<block>
Constructs an array with the results of repeating the specified block for a number of times.
```lisp
(repeat times 4 (i))
; [0,1,2,3]

(repeat from 1 times 10 step 2 (i))
; [1,3,5,7,9,11,13,15,17,19]

(repeat x from 4 to 6
    (pow 2 (x))
)
; [16,32,64]
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

## `block` \<block>
Returns the value returned by the block. Mainly used to write cleaner code.

```lisp
(block
    (set a 12)
    (set b 13)
    (+ (a) (b))
)
; 25
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

## `try` \<block> [catch \<block>] [finally \<block>]
Executes the specified block and returns its result. If an error occurs, the `catch` block will be executed and its result returned. The `finally` block will be executed regardless if there was an error or not.

<small>Note: The error message will be available in the `err` variable. And the exception object in the `ex` variable.</small>

```lisp
(try
    (throw "Something happened")
catch
    (echo "Error: " (err))
finally
    (echo "Done")
)
; Error: Something happened
; Done
```

## `throw` [\<expr>]
Throws an error. The value to throw can be anything, but note that it will be converted to a string first. If no parameter is specified, the internal variable `err` will be used as message.

```lisp
(try
    (throw (& message "Something happened" ))
catch
    (echo "Error: " (err))
)
; Error: {"message":"Something happened"}

(try
    (set err "Hello!")
    (throw)
catch
    (echo "Error: " (err))
)
; Error: Hello!
```

## `assert` \<condition> [\<message>]
Throws an error if the specified condition is not true.

```lisp
(assert (eq 1 2) "1 is not equal to 2")
; Error: 1 is not equal to 2

(assert false)
; Error: Assertion failed
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

<br/>

# Sentinel

## `sentinel::password`
## `sentinel::status`
## `sentinel::auth-required`
## `sentinel::privilege-required`
## `sentinel::has-privilege`
## `sentinel::level-required`
## `sentinel::has-level`
## `sentinel::get-level`
## `sentinel::valid`
## `sentinel::validate`
## `sentinel::login`
## `sentinel::authorize`
## `sentinel::login:manual`
## `sentinel::login:forced`
## `sentinel::logout`
## `sentinel::reload`

<br/>

# Shield

## `_shield::field`
## `shield::begin`
## `shield::end`
## `shield::validate`
## `shield::validateData`

<br/>

# Locale

## `locale::number`
## `locale::integer`
## `locale::time`
## `locale::date`
## `locale::datetime`
## `locale::gmt`
## `locale::utc`
## `locale::iso_date`
## `locale::iso_time`
## `locale::iso_datetime`
## `locale::format`

<br/>

# Database

## `escape`
## `db::escape`
## `db::escape:name`
## `db::scalar`
## `db::scalars`
## `db::row`
## `db::row:array`
## `db::table`
## `db::table:array`
## `db::reader`
## `db::exec`
## `db::update`
## `db::insert`
## `db::lastInsertId`
## `db::affectedRows`
## `db::fields:update`
## `db::fields:insert`

<br/>

# DateTime

## `datetime::now`
## `datetime::now:int`
## `datetime::parse`
## `datetime::int`
## `datetime::sub`
## `datetime::diff`
## `datetime::add`
## `datetime::date`
## `datetime::time`
## `datetime::format`

<br/>

# Image

## `image::load`
## `image::save`
## `image::output`
## `image::data`
## `image::width`
## `image::height`
## `image::resize`
## `image::scale`
## `image::fit`
## `image::cut`
## `image::crop`
## `image::smartcut`

<br/>

# Path

## `path::fsroot`
## `path::basename`
## `path::extname`
## `path::name`
## `path::normalize`
## `path::dirname`
## `path::resolve`
## `path::join`
## `path::is_file`
## `path::is_dir`
## `path::exists`
## `path::chmod`
## `path::rename`

<br/>

# File

## `file::size`
## `file::dump`
## `file::mtime`
## `file::atime`
## `file::touch`
## `file::read`
## `file::write`
## `file::append`
## `file::remove`
## `file::copy`
## `file::create`

<br/>

# Directory

## `dir::create`
## `dir::files`
## `dir::dirs`
## `dir::entries`
## `dir::files:recursive`
## `dir::dirs:recursive`
## `dir::entries:recursive`
## `dir::remove`

<br/>

# HTTP

## `http::get`
## `http::post`
## `http::fetch`
## `http::header`
## `http::method`
## `http::auth`
## `http::code`
## `http::content-type`
## `http::data`

<br/>

# Session

## `session`
## `session::open`
## `session::close`
## `session::destroy`
## `session::clear`
## `session::name`
## `session::id`
## `session::isopen`
## `session::data`

<br/>

# Utils

## `configuration`
## `config`
## `c`
## `strings`
## `s`
## `s::lang`
## `resources`

## `gateway`
## `gateway::redirect`
## `gateway::flush`
## `gateway::persistent`

## `utils::rand`
## `utils::randstr`
## `utils::randstr:base64`
## `utils::uuid`
## `utils::unique`
## `utils::sleep`
## `utils::base64::encode`
## `utils::base64::decode`
## `utils::hex::encode`
## `utils::hex::decode`
## `utils::url::encode`
## `utils::url::decode`
## `utils::serialize`
## `utils::deserialize`
## `utils::urlSearchParams`
## `utils::html::encode`
## `utils::html::decode`
## `utils::json::stringify`
## `utils::json::prettify`
## `utils::json::parse`
## `utils::html`
## `utils::shell`

## `utils::hashes`
## `utils::hash`
## `utils::hash:binary`
## `utils::hmac`
## `utils::hmac:binary`

<br/>

# Array

## `array::new`
## `array::sort:asc`
## `array::sort:desc`
## `array::sortl:asc`
## `array::sortl:desc`
## `array::push`
## `array::unshift`
## `array::pop`
## `array::shift`
## `array::first`
## `array::last`
## `array::remove`
## `array::indexof`
## `array::length`
## `array::append`
## `array::unique`
## `array::reverse`
## `array::clear`

<br/>

# Map

## `map::new`
## `map::sort:asc`
## `map::sort:desc`
## `map::sortk:asc`
## `map::sortk:desc`
## `map::keys`
## `map::values`
## `map::set`
## `map::get`
## `map::remove`
## `map::keyof`
## `map::length`
## `map::merge`
## `map::clear`

<br/>

# Regular Expressions

## `re::matches`
## `re::matchFirst`
## `re::matchAll`
## `re::split`
## `re::replace`
## `re::extract`

<br/>

# Wind

## `trace` \<message...>
Writes the specified message(s) separated by space to the log file.

```lisp
(trace "Hello" "World")
; String "Hello World" will be in the `system.log` file in the `logs` folder.
```

## `trace::alt` \<name> \<message...>
Writes the specified message(s) separated by space to the specified log file in the `logs` folder. No need to add path nor extension.

```lisp
(trace::alt "mylog" "Hello" "World")
; String "Hello World" will be in the `mylog.log` file in the `logs` folder.
```

## `header` \<header-line>
Sets a header of the current HTTP response.

```lisp
(header "Content-Type: application/json")
; Header will be set in HTTP response.
```

## `content-type` \<mime-type>
Sets the content type of the current HTTP response. This is a useful shortcut for `(header "Content-Type: <mime-type>")`.

```lisp
(content-type "text/html")
; Header "Content-Type: text/html" will be set in HTTP response.
```

## `call` \<name> [\<varName> \<varValue>...]
Calls the specified API function with the given parameters which will be available as globals to the target. Returns the response object.

The context of the target will be set to the current context, so any global variables will be available to the target function.

```lisp
(call "users.list" count 1 offset 10)
; Executes file `rcore/users/list.fn` in the current context.
```

## `icall` \<name> [\<varName> \<varValue>...]
Performs an **isolated call** to the specified API function with the given parameters which will be available as globals to the target. Returns the response object.

The context of the target will be set to a new context, so any global variables will **not be** available to the target function (except the pure `global` object).

```lisp
(icall "users.get" id 12)
; Executes file `rcore/users/get.fn` in a new context.
```

## `return` [\<data>]
Returns the specified data (or an empty object if none specified) to the current invoker (not the same as a function caller). The invoker is most of time the browser, except when using `call` or `icall`.

The response for the browser is always formatted for consistency:
- If `data` is an object and doesn't have the `response` field it will be added with the value `200` (OK).
- If `data` is an array, it will be placed in a field named `data` of the response object, with `response` code 200.

```lisp
(return (&))
; {"response":200}

(return (# 1 2 3))
; {"response":200,"data":[1,2,3]}
```

## `stop` [\<data>]
Stops execution of the current request and returns the specified data to the browser. If none specified, nothing will be returned.

Response is formatted following the same rules as `return`.

```lisp
(stop)
; (empty-string)
```

## `evt::init`

Initializes server-sent events by setting several HTTP headers in the response and configuring the Gateway to persist and disable any kind of output buffering.

The following headers are set:
- Content-Type: text/event-stream; charset=utf-8
- Transfer-Encoding: identity
- Content-Encoding: identity
- Cache-Control: no-store
- X-Accel-Buffering: no

```lisp
(evt::init)
; Headers will be set and Gateway ready for SSE.
```

## `evt::send` [\<event-name>] \<data>

Sends the specified data to the browser as a server-sent event. If no event name is specified, the default event name `message` will be used.

```lisp
(evt::send "Hello World")
; event: message
; data: Hello World

(evt::send "info" (& list (# 1 2 3)))
; event: info
; data: {"list":[1,2,3]}
```

## `evt::alive`

Sends a comment line `:alive` to the browser to keep the connection alive if the last message sent was more than 30 seconds ago. Returns `false` if the connection was already closed by the browser.

```lisp
(when (evt::alive)
    ; Do something ...
    (utils::sleep 1)
)
```
