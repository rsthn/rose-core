[&laquo; Go Back](./README.md)
# Core


### (`global`)
Global object available across all contexes.
```lisp
(set global.name "Jenny")
(echo "My name is (global.name)")
; My name is Jenny
```

### (`include` \<source-path...>)
Includes one or more source files and evaluates them as if they were written in the current source file.
```lisp
(include "lib/math")
(echo (math:PI))
; 3.141592
```

### (`import` \<source-path> [as \<namespace-name>])
Imports definitions from a source file into a namespace, or the current namespace if none specified.
```lisp
(include "lib/math" as "m")
(echo (m:PI))
; 3.141592
```

### (`#` [values...])<br/>[ values... ]
Constructs an array. Note that the first form (#) is legacy from previous syntax.
```lisp
(# 1 2 3 4 5)
; [1,2,3,4,5]

["a" "b" "c"]
; ["a","b","c"]
```

### (`&` [key value...])<br/>{ key value... }
Constructs a map with the given key-value pairs. Note that the first form (&) is legacy from previous syntax.
```lisp
(& "name" "Jenny" "age" 25)
; {"name":"Jenny","age":25}

{ name "Jon" age 36 }
; {"name":"Jon","age":36}
```

### (`echo` \<value...>)
Writes the specified values to standard output and adds a new-line at the end.
```lisp
(echo "Hello" " " "World")
(echo "!")
; Hello World
; !
```

### (`print` \<value...>)
Writes the specified values to standard output.
```lisp
(print "Hello" "World" "!")
(print "!")
; HelloWorld!!
```

### (`trace` \<message...>)
Writes the specified message(s) separated by space to the default log file.
```lisp
(trace "Hello" "World")
; String "Hello World" will be in the `system.log` file in the `logs` folder.
```

### (`trace-alt` \<log-name> \<message...>)
Writes the specified message(s) separated by space to the specified log file in the `logs` folder. No need to add path or extension.
```lisp
(trace-alt "mylog" "Hello" "World")
; String "Hello World" will be in the `mylog.log` file in the `logs` folder.
```

### (`nop` ...)
Does nothing and returns `null`, any arguments will not be evaluated. Useful to "comment out" a block of code.
```lisp
(nop (echo "Good day"))
; null
```

### (`len` \<value>)
Returns the length of the given text or number of elements in a structure.
```lisp
(len "Hello World")
; 11

(len [1 2 3 4 5])
; 5
```

### (`str` \<value>)
Converts the given value to a string.
```lisp
(str 123)
; 123
```

### (`int` \<value>)
Converts the given value to an integer.
```lisp
(int "123")
; 123
```

### (`bool` \<value>)
Converts the given value to a boolean.
```lisp
(bool "true")
; true
(bool 1)
; true
```

### (`number` \<value>)
Converts the given value to a number.
```lisp
(number "123.45")
; 123.45
```

### (`set` \<target> \<value>)
Sets the value of one or more variables in the data context.
```lisp
(set name "John")
(set person.name "Jane")
```

### (`inc` \<target> [value])
Increases the value of a variable by the given value (or `1` if none provided).
```lisp
(inc count)
; 1
(inc count 5)
; 6
```

### (`dec` \<target> [value])
Decreases the value of a variable by the given value (or `1` if none provided).
```lisp
(dec count)
; -1
(dec count 5)
; -6
```

### (`append` \<target> \<value...>)
Appends the given value(s) to the variable.
```lisp
(append name "John")
(append name " Doe")
; John Doe
```

### (`unset` \<target...>)
Removes one or more variables from the data context.
```lisp
(set val "Hi!")
(val)
; Hi!
(unset val)
(val)
; Error: Function `val` not found.
```

### (`get-fn` function-name)
Returns a reference to the specified function.
```lisp
(get-fn "file:read")
; [Function file:read]
```

### (`set-fn` function-name function-reference)
Sets the reference of a function in the root context. If the reference is `null` the function will be removed.
```lisp
(set-fn "file:read" (fn value (echo "Want to read file: (value)")))
(file:read 'info.txt')
; Want to read file: info.txt

(set-fn "file:read" null)
(file:read 'info.txt')
; Error: function `file:read` not found
```

### (`typeof` \<value>)
Returns a string with the type-name of the value. Possible values are: `null`, `string`, `bool`, `array`,
`object`, `int`, `number`, and `function`.
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

### (`dump` \<value>)
Converts the value to dumpable format (JSON).
```lisp
(dump (# 1 2 3))
; [1,2,3]
```

### (`concat` \<value...>)
Converts all values to a string and concatenates them.
```lisp
(concat "Hello" " " "World")
; Hello World
```

### (`chr` \<value>)
Returns the character corresponding to the given binary value.
```lisp
(chr 65)
; A
```

### (`ord` \<value>)
Returns the binary value of the first character in the given string.
```lisp
(ord "A")
; 65
```

### (`not` \<value>)
Returns the logical NOT of the value.
```lisp
(not 8)
; false

(not false)
; true

(not 0)
; true
```

### (`neg` \<value>)
Negates the value.
```lisp
(neg 8)
; -8

(neg -8)
; 8
```

### (`and` \<value...>)
Checks each value and returns the first **falsey** found or the last one in the sequence.
```lisp
(and true 12 "Hello")
; Hello

(and 1 2 3)
; 3

(and 1 0 12)
; 0

(and true true true)
; true

(and false 12 2)
; false
```

### (`or` \<value...>)
Checks each value and returns the first **truthy** found or the last one in the sequence.
```lisp
(or false 12 "Hello")
; 12

(or 0 0 0)
; 0

(or 1 2 3)
; 1

(or 0 false "Hello" false)
; Hello
```

### (`coalesce` \<value...>)<br/>(`??` \<value...>)
Returns the first value that is not `null` or `null` if all are `null`.
```lisp
(coalesce null 12 "Hello")
; 12

(coalesce null null null)
; null

(?? null null "Hello")
; Hello
```

### (`bit-not` \<value>)
Return the result of a NOT (bitwise) operation.
```lisp
(bit-not 12)
; -13
```

### (`bit-and` \<value-1> \<value-2>)
Returns the result of the AND (bitwise) operation.
```lisp
(bit-and 7 29)
; 5
```

### (`bit-or` \<value-1> \<value-2>)
Returns the result of the OR (bitwise) operation.
```lisp
(bit-or 7 29)
; 31
```

### (`bit-xor` \<value-1> \<value-2>)
Returns the result of the XOR (bitwise) operation.
```lisp
(bit-xor 7 29)
; 26
```

### (`in?` \<iterable> \<value> [val-true=true] [val-false=false])
Checks if an iterable (map, array or string) has a value.
```lisp
(in? [1 2 3] 2)
; true

(in? {name "John"} "name")
; true

(in? "Hello" "l")
; true
```

### (`eq?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` is equal to `value2`, returns `val-true` or `val-false` (loose type comparison).
```lisp
(eq? 12 "12")
; true

(eq? 0 false)
; true

(eq? false null)
; true

(eq? true 12)
; true

(eq? 12 13)
; false
```

### (`eqq?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` is equal to `value2` and are of the same type, returns `val-true` or `val-false`.
```lisp
(eqq? 12 "12")
; false

(eqq? 0 false)
; false

(eqq? false null)
; false

(eqq? true 12)
; false

(eqq? 12 12)
; true

(eqq? "X" "X)
; true
```

### (`ne?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` is not equal to `value2`, returns `val-true` or `val-false`.
```lisp
(ne? 12 "12")
; false

(ne? 0 false)
; false

(ne? false null)
; false

(ne? true 12)
; false

(ne? 12 13)
; true
```

### (`lt?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` \< `value2`, returns `val-true` or `val-false`.
```lisp
(lt? 1 2)
; true

(lt? 10 10)
; false

(lt? 10 5)
; false
```

### (`le?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` \<= `value2`, returns `val-true` or `val-false`.
```lisp
(le? 1 2)
; true

(le? 10 10)
; true

(le? 10 5)
; false
```

### (`gt?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` > `value2`, returns `val-true` or `val-false`.
```lisp
(gt? 1 2)
; false

(gt? 10 10)
; false

(gt? 10 5)
; true
```

### (`ge?` \<value1> \<value2> [val-true=true] [val-false=false])
Checks if `value1` >= `value2`, returns `val-true` or `val-false`.
```lisp
(ge? 1 2)
; false

(ge? 10 10)
; true

(ge? 10 5)
; true
```

### (`null?` \<value> [val-true=true] [val-false=false])
Checks if the value is `null`, returns `val-true` or `val-false`.
```lisp
(null? null)
; true

(null? 0)
; false

(null? null "Yes" "No")
; Yes
```

### (`not-null?` \<value> [val-true=true] [val-false=false])
Checks if the value is not `null`, returns `val-true` or `val-false`.
```lisp
(not-null? null)
; false

(not-null? 0)
; true

(not-null? null "Yes" "No")
; No
```

### (`zero?` \<value> [val-true=true] [val-false=false])
Checks if the value is zero, returns `val-true` or `val-false`.
```lisp
(zero? 0)
; true

(zero? 1)
; false

(zero? null)
; false

(zero? 0.0)
; true
```

### (`even?` \<value> [val-true=true] [val-false=false])
Checks if the value is even, returns `val-true` or `val-false`.
```lisp
(even? 12)
; false

(even? 13)
; true
```

### (`odd?` \<value> [val-true=true] [val-false=false])
Checks if the value is odd, returns `val-true` or `val-false`.
```lisp
(odd? 12)
; false

(odd? 13)
; true
```

### (`true?` \<value> [val-true=true] [val-false=false])
Checks if the value is `true` (strong type checking).
```lisp
(true? true)
; true

(true? 1)
; false
```

### (`false?` \<value> [val-true=true] [val-false=false])
Checks if the value is `false` (strong type checking).
```lisp
(false? true)
; true

(false? 1)
; false
```

### (`int?` \<value> [val-true=true] [val-false=false])
Checks if the value is an integer.
```lisp
(int? 12)
; true

(int? 12.5)
; false
```

### (`str?` \<value> [val-true=true] [val-false=false])
Checks if the value is a string.
```lisp
(str? "Hello")
; true

(str? 12)
; false
```

### (`bool?` \<value> [val-true=true] [val-false=false])
Checks if the value is a boolean.
```lisp
(bool? true)
; true

(bool? 1)
; false
```

### (`number?` \<value> [val-true=true] [val-false=false])
Checks if the value is a number.
```lisp
(number? 12)
; true

(number? 12.5)
; true

(number? "12")
; false
```

### (`array?` \<value> [val-true=true] [val-false=false])
Checks if the value is an array.
```lisp
(array? (# 1 2 3))
; true
```

### (`map?` \<value> [val-true=true] [val-false=false])
Checks if the value is a map.
```lisp
(map? (& value "Yes"))
; true
```

### (`fn?` \<value> [val-true=true] [val-false=false])
Checks if the value is a function.
```lisp
(fn? (fn x 0))
; true
```

### (`+` \<values...>)
Returns the sum of the given values.
```lisp
(+ 1 2 3)
; 6
```

### (`-` \<values...>)
Returns the result of subtracting each value by the next one.
```lisp
(- 10 2 3)
; 5
```

### (`*` \<values...>)
Returns the result of multiplying the given values.
```lisp
(* 2 -1.5 3.25)
; -9.75
```

### (`mul` \<values...>)
Returns the integer result of multiplying the given values.
```lisp
(mul 2 -1.5 3.25)
; -9
```

### (`/` \<values...>)
Returns the result of dividing each value by the next one.
```lisp
(/ 100 10 3)
; 3.3333333333333
```

### (`div` \<values...>)
Returns the integer result of dividing each value by the next one.
```lisp
(div 100 10 3)
; 3
```

### (`mod` \<values...>)
Returns the result of the modulo operation.
```lisp
(mod 100 15)
; 2
```

### (`pow` \<values...>)
Returns the result of raising each number to the next one.
```lisp
(pow 3 2 4)
; 6561
```

### (`min` \<values...>)
Returns the minimum value of the given values.
```lisp
(min 1 2 3)
; 1
```

### (`max` \<values...>)
Returns the maximum value of the given values.
```lisp
(max 1 2 3)
; 3
```

### (`shl` \<values...>)
Returns the result of shifting the bits of the first value to the left by the second value.
```lisp
(shl 3 2)
; 12
```

### (`shr` \<values...>)
Returns the result of shifting the bits of the first value to the right by the second value.
```lisp
(shr 16 2)
; 4
```

### (`join` [glue] \<array>)
Joins the array into a string. If `glue` is provided, it will be used as separator.
```lisp
(join (# a b c))
; abc

(join "_" (# a b c))
; a_b_c
```

### (`split` [delimiter] \<string>)
Splits the string by the specified delimiter (or empty string if none specified). Returns an array.
```lisp
(split "," "A,B,C")
; ["A","B","C"]

(split "Hi!")
; ["H","i","!"]
```

### (`for` [key-var:val-var | val-var] [`in`] \<array> \<block>)
Evaluates the given block for each of the items in the array and returns the **original array**.
<br/>NOTE: Extra variables `i#` and `i##` (iterator variable with suffix `#` and `##`) are automatically introduced to
<br/>hold the index or key and numeric index of each item respectively (if no variable provided). Note that the later (##)
<br/>will always have a numeric value.
```lisp
(for x [1 2 3]
    (echo (* (x) 1.5))
)
; 1.5
; 3
; 4.5

(for key: val { "a" 1 "b" 2 }
    (echo "key: (key) value: (val)")
)
; key: a value: 1
; key: b value: 2
```

### (`break`)
Exits the current inner most loop.
```lisp
(for i [1 2 3]
    (echo (i))
    (break)
)
; 1
```

### (`continue`)
Skips execution and continues the next iteration of the current inner most loop.
```lisp
(for i [1 2 3 4 5 6 7 8 9 10]
    (when (odd? (i))
        (continue))
    (echo (i))
)
; 2 4 6 8 10
```

### (`?` \<condition> \<a> [b])
Returns `a` if the expression is `true` otherwise returns `b` or empty string if `b` was not specified. This is a short version of the `if` function.
```lisp
(? true "Yes" "No")
; Yes
(? false "Yes" "No")
; No
(? false "Yes")
; <empty-string>
```

### (`if` \<condition> \<value> [elif \<condition> \<value-2>...] [else \<value-3>])
Returns the value if the expression is `true`, otherwise attempts to find a matching `elif` or an `else` if provided.
```lisp
(if true
   (echo "Yes")
else
   (echo "No")
)
; Yes
```

### (`block` \<value>)
Returns the value in the block, used mainly to write cleaner code.
```lisp
(block
    (set a 12)
    (set b 13)
    (+ (a) (b))
)
; 25
```

### (`when` \<condition> \<value>)
Returns the value if the expression is `true`.
```lisp
(when (eq 12 12) "Ok")
; Ok
```

### (`when-not` \<condition> \<value>)
Returns the value if the expression is `false`.
```lisp
(when-not (eq 12 13) "Ok")
; Ok
```

### (`switch` \<expr> [\<case> \<value> ...] [default \<value>])
Compares the result of the given expression against one of the case values (loose comparison). Executes the respective
case block, or the `default` block if none matches.
```lisp
(switch 3
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

### (`gather` [varname='i'] [`from` \<number>] [`to` \<number>] [`times` \<number>] [`step` \<number>] \<block>)
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

### (`repeat` [varname='i'] [`from` \<number>] [`to` \<number>] [`times` \<number>] [`step` \<number>] \<block>)
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

### (`loop` \<block>)
Repeats the specified block **indefinitely** until a `break` is found, be careful with this one.
```lisp
(loop
    (echo "Hello")
    (break)
)
; Hello
```

### (`while` \<condition> \<block>)
Repeats the specified block until the condition is `false` or a "break" is found.
```lisp
(set i 0)
(while (lt? (i) 10)
    (when-not (zero? (i))
        (print ":"))

    (print (i))
    (inc i)
)
; 0:1:2:3:4:5:6:7:8:9
```

### (`map` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Maps each value in the array/map to a new value returned by the block.
```lisp
(map [1 2 3] (* (i) 1.5))
; [1.5, 3, 4.5]

(map x [1 2 3] (pow 2 (x)))
; [2, 4, 8]

(map key:val { "a" 1 "b" 2 "c" 3 } (concat (key) "=" (val)) )
; ["a=1", "b=2", "c=3"]
```

### (`filter` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Returns a new array/map with the values that pass the test implemented by the block.
```lisp
(filter [1 2 3 4 5] (lt? (i) 3))
; [1, 2]

(filter x in [1 2 3 4 5] (odd? (x)))
; [1, 3, 5]
```

### (`all` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Returns `true` if all the values in the array/map pass the test implemented by the block.
```lisp
(all [1 2 3 4 5] (lt? (i) 6))
; true

(all x in [1 2 3 4 5] (odd? (x)))
; false

(all key:val { "a" 1 "b" 2 "c" 3 } (lt? (val) 4))
; true
```

### (`any` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Returns `true` if at least one of the values in the array/map pass the test implemented by the block.
```lisp
(any [1 2 3 4 5] (lt? (i) 2))
; true

(any x in [2 4 16] (odd? (x)))
; false
```

### (`find` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Returns the first value in the array/map that passes the test implemented by the block or `null` if none found.
```lisp
(find [1 2 3 4 5] (gt? (i) 3))
; 4

(find x in [2 4 16] (odd? (x)))
; null

(find key:val { "a" 1 "b" 2 "c" 3 } (eq? (key) "c"))
; 3
```

### (`find-index` [key-var:val-var | val-var] [`in`] \<iterable> \<block>)
Returns the index of the first value in the array/map that passes the test implemented by the block or `null` if none found.
```lisp
(find-index [1 2 3 4 5] (gt? (i) 3))
; 3

(find-index x in [2 4 16] (odd? (x)))
; null

(find-index key:val { "a" 1 "b" 2 "c" 3 } (eq? (key) "c"))
; 2
```

### (`reduce` [key-var:val-var | val-var] [accum-var='s'] [accum-initial=0] [`in`] \<iterable> \<block>)
Reduces the array/map to a single value using the block to process each value. An accumulator and iteration values are
passed to the block.
```lisp
(reduce x in [1 2 3 4 5] (+ (s) (x)))
; 15

(reduce key:val in { "a" 1 "b" 2 "c" 3 } (+ (s) (val)))
; 6

(reduce x accum 0 [1 7 15] (+ (accum) (x)))
; 23
```

### (`mapify` [key-var:val-var | val-var] [`in`] \<iterable> \<key-expr> [value-expr])
Returns a new map created with the specified key-expression and value-expression.
```lisp
(mapify i [1 2 3] (concat "K" (i)) (pow 2 (i)))
; {"K1":2,"K2":4,"K3":8}

(mapify idx:val in [1 2 3] (* (idx) (val)))
; {0:1,2:2,6:3}
```

### (`groupify` [key-var:val-var | val-var] [`in`] \<iterable> \<key-expr> [value-expr])
Returns a new map created by grouping all values having the same key-expression.
```lisp
(groupify i [1 2 3 4 5 6 7 8 9 10] (mod (i) 3))
; {"1":[1,4,7,10],"2":[2,5,8],"0":[3,6,9]}

(groupify i [1 2 3 4] (mod (i) 2) (* 3 (i)))
; {"1":[3,9],"0":[6,12]}
```

### (`try` \<block> [`catch` \<block>] [`finally` \<block>])
Executes the specified block and returns its result. If an error occurs, the `catch` block will be executed 
and its result returned. The `finally` block will be executed regardless if there was an error or not.
<br/>Note: The error message will be available in the `err` variable. And the exception object in the `ex` variable.
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

### (`throw` [message])
Throws an error. The value to throw can be anything, but note that it will be converted to a string first. If no parameter
is specified, the internal variable `err` will be used as message.
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

### (`assert` \<condition> [message])
Throws an error if the specified condition is not `true`.
```lisp
(assert (eq 1 2) "1 is not equal to 2")
; Error: 1 is not equal to 2

(assert false)
; Error: Assertion failed
```

### (`assert-eq` \<value1> \<value2> [message])
Throws an error if the specified values are not equal.
```lisp
(assert-eq 1 2 "Oh no!")
; Error: Oh no! => 1 != 2
```

### (`pipe` \<expr...>)
Executes one or more expressions and makes the result of each available to the next via the internal `_` variable.
```lisp
(pipe
    10
    (+ 2 _)
    (pow _ 2)
)
; 144
```

### (`expand` \<template> [data])
Expands the specified template string using the given data. The result will always be a string. The s-expression enclosing
symbols will be '{' and '}' respectively instead of the usual parenthesis. If no data is provided, the current context will be used.
```lisp
(expand "Hello {name}!" (& name "John"))
; Hello John!

(expand "Hello {upper {name}}!" (& name "Jane"))
; Hello JANE!
```

### (`eval` \<template> [data])
Evaluates the specified template string using the given data. If no data is provided, the current context will be used.
```lisp
(eval `(eq? 1 1)`)
; true

(eval `(concat (upper (name)) ' ' Doe)` (& name "John"))
; JOHN Doe
```

### (`with` [var='i'] [`as`] \<value> \<block>)
Introduces a new temporal variable with the specified value to be used in the block, the variable will be returned to its original
state (or removed) once the `with` block is completed. Returns the value returned by the block.
```lisp
(with a 12
    (echo (a))
)
(echo (a))
; 12
; Error: Function `a` not found.
```

### (`yield` [level] \<value>)
Yields a value to an inner block at a desired level to force the result to be the specified value, thus efectively
exiting all the way to that block. If no level is specified, the current block will be used.
<br/>Note: The inner-most block is level-1.
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

### (`exit` [level])
Yields a `null` value to an inner block at a desired level, efectively exiting all the way to that block. If no
level is specified, the current block will be used.
<br/>Note: The inner-most block is level-1.
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

### (`ns` [public|private] name)
Sets the active namespace for any `def-*` statements.
```lisp
(ns math)
(def PI 3.141592)

(echo (math:PI))
; 3.141592
```

### (`fn` [param...] \<block>)
Creates a function with the specified parameters and function body. Returns the function object.
```lisp
(set sum (fn a b
    (+ (a) (b))
))

((sum) 5 7)
; 12
```

### (`def` [private|public] \<var-name> \<value>)
Defines a constant variable in the current scope. The variable can only be changed by overriding it with another `def`.
```lisp
(def a "Hello")

(def-fn x
    (concat (a) " World"))

(x)
; Hello World
```

### (`def-fn` [private|public] \<fn-name> [param-name...] \<block>)
Defines a function with the given name, parameters and body block. Functions are isolated and do not have access to any of the
outer scopes (except definitions created with `def` or `def-fn`), however the `local` object can be used to access the main
scope (file where the function is defined), and `global` can be used to access the global scope.
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

### (`ret` [value])
Returns from a function with the specified value (or `null` if none specified).
```lisp
(def-fn getval
    (ret 3)
)
(getval)
; 3
```

### (`zipmap` \<keys> \<values>)
Creates a new map by zipping the respective keys and values together.
```lisp
(zipmap ["a" "b" "c"] [1 2 3])
; {"a":1,"b":2,"c":3}
```

### (`map-get` \<keys> \<map>)
Extracts the specified keys from a given map and returns a new map.
```lisp
(map-get ["a" "b"] {"a" 1 "b" 2 "c" 3})
; {"a":1,"b":2}
```

### (`debug:fn` [prefix])
Returns the name of all functions in the root context optionally with some prefix.
```lisp
(debug:fn "crypto")
; ["crypto:hash-list","crypto:hash", ...]
```

### (`stop` [value])
Stops execution of the current request and returns the specified data to the browser. If none
specified, nothing will be returned.
<br/>Response is formatted following the same rules as `return`.
```lisp
(stop "Hello, World!")
; Hello, World!
```

### (`return` [data])
Returns the specified data (or an empty object if none specified) to the current invoker (not the same as a
function caller). The invoker is most of time the browser, except when using `call` or `icall`.
<br/>The response for the browser is always formatted for consistency:
<br/>- If `data` is an object and doesn't have the `response` field it will be added with the value `200` (OK).
<br/>- If `data` is an array, it will be placed in a field named `data` of the response object, with `response` code 200.
```lisp
(return (&))
; {"response":200}

(return (# 1 2 3))
; {"response":200,"data":[1,2,3]}
```

### (`call` \<function-name> [key value...])
Calls the specified API function with the given parameters which will be available as globals to the target,
returns the response object.
<br/>The context of the target will be set to the **current context**, so any global variables will be available
<br/>to the target function.
```lisp
(call "users.list" count 1 offset 10)
; Executes file `fn/users/list.fn` in the current context with variables `count` and `offset`.
```

### (`icall` \<function-name> [key value...])
Performs an **isolated call** to the specified API function with the given parameters which will be available as
global variables to the target. Returns the response object.
<br/>The context of the target will be set to a new context, so any global variables in the caller will **not be**
<br/>available to the target function (except the pure `global` object).
```lisp
(icall "users.get" id 12)
; Executes file `fn/users/get.fn` in a new context with variable `id`.
```
