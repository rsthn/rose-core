# Functions
- [Configuration](#configuration)
- [Core](#core)
- [Array](#array)
- [Cookies](#cookies)
- [Crypto](#crypto)
- [Database](#database)
- [DateTime](#datetime)
- [Image](#image)
- [Map](#map)
- [OpenSSL](#openssl)
- [HTTP Requests](#http-requests)
- [Session](#session)
- [Server-Sent Events (SSE)](#server-sent-events-sse)
- [Utilities](#utilities)
- [Gateway](#gateway)
- [Directory](#directory)
- [File](#file)
- [Path](#path)
- [Locale](#locale)
- [Math](#math)
- [Regular Expressions](#regular-expressions)
- [Text](#text)

<br/><br/>

<br/><br/>

# Configuration


### (`config`)
Object containing the currently loaded system configuration.
```lisp
(config)
; {"General": {"version": "1.0.0"}}
```

### (`config.env`)
Indicates what environment mode was used to load the configuration.
```lisp
(config.env)
; dev
```

### (`config:parse` \<config-string>)
Parses the given configuration buffer and returns a map. The buffer data is composed of key-value pairs
separated by equal-sign (i.e. Name=John), and sections enclosed in square brakets (i.e. [General]).
<br/>
<br/>Note that you can use the equal-sign in the field value without any issues because the parser will look
<br/>only for the first to delimit the name.
<br/>
<br/>If a multi-line value is desired, single back-ticks (`) can be used after the equal sign to mark a start, and
<br/>on a single line to mark the end. Each line will be trimmed first before concatenating it to the value, and
<br/>new-line character is preserved.
```lisp
(config:parse "[General]\nversion=1.0.0")
; {"General": {"version": "1.0.0"}}
```

### (`config:str` \<value>)
Converts the specified object to a configuration string. Omit the `value` parameter to use the
currently loaded configuration object.
```lisp
(config:str (& general (& version "1.0.0")))
; [General]
; version=1.0.0
```

<br/><br/>

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
(append name " Doe" "!")
; John Doe!
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

### (`not-in?` \<iterable> \<value> [val-true=true] [val-false=false])
Checks if an iterable (map, array or string) does NOT have a value.
```lisp
(not-in? [1 2 3] 2)
; false

(not-in? {name "John"} "name")
; false

(not-in? "Hello" "p")
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

### (`not-zero?` \<value> [val-true=true] [val-false=false])
Checks if the value is NOT zero, returns `val-true` or `val-false`.
```lisp
(not-zero? 0)
; false

(not-zero? 1)
; true

(not-zero? null)
; true

(not-zero? 0.0)
; false
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

### (`filter` [key-var:val-var | val-var] [`in`] \<iterable> [block])
Returns a new array/map with the values that pass the test implemented by the block.
```lisp
(filter [1 2 3 4 5] (lt? (i) 3))
; [1, 2]

(filter x in [1 2 3 4 5] (odd? (x)))
; [1, 3, 5]

(filter x in [0 2 0 4 5])
; [2, 4, 5]
```

### (`all` [key-var:val-var | val-var] [`in`] \<iterable> [block])
Returns `true` if all the values in the array/map pass the test implemented by the block.
```lisp
(all [1 2 3 4 5] (lt? (i) 6))
; true

(all x in [1 2 3 4 5] (odd? (x)))
; false

(all key:val { "a" 1 "b" 2 "c" 3 } (lt? (val) 4))
; true

(all [1 0 3])
; false
```

### (`any` [key-var:val-var | val-var] [`in`] \<iterable> [block])
Returns `true` if at least one of the values in the array/map pass the test implemented by the block.
```lisp
(any [1 2 3 4 5] (lt? (i) 2))
; true

(any x in [2 4 16] (odd? (x)))
; false

(any [0 0 1])
; true
```

### (`find` [key-var:val-var | val-var] [`in`] \<iterable> [block])
Returns the first value in the array/map that passes the test implemented by the block or `null` if none found.
```lisp
(find [1 2 3 4 5] (gt? (i) 3))
; 4

(find x in [2 4 16] (odd? (x)))
; null

(find key:val { "a" 1 "b" 2 "c" 3 } (eq? (key) "c"))
; 3

(find [false null 5 false])
; 5
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

(find-index [null false 'Ok'])
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

### (`range` [start=0] \<end> [step=1])
Returns a sequence of integer numbers for the specified range (end-exclusive).
```lisp
(range 1 10)
; [1,2,3,4,5,6,7,8,9]

(range 4)
; [0,1,2,3]

(range 1 10 2)
; [1,3,5,7,9]
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

<br/><br/>

# Array


### (`array` [values...])<br/>(`#` [values...])<br/>[ values... ]
Constructs an array. Note that the second form (#) is legacy from previous syntax.
```lisp
(array 1 2 3)
; [1,2,3]

(# 1 2 3)
; [1,2,3]

[1 2 3]
; [1,2,3]
```

### (`array:sort` \<var-a> \<var-b> \<array> \<block>)
Sorts the array in place using a custom comparison function.
```lisp
(array:sort a b (array 3 1 2) (- (a) (b)) )
; [1, 2, 3]
```

### (`array:sort-asc` \<array>)
Sorts the array in place in ascending order.
```lisp
(array:sort-asc (array 3 1 2))
; [1, 2, 3]
```

### (`array:sort-desc` \<array>)
Sorts the array in place in descending order.
```lisp
(array:sort-desc (array 3 1 2 15 -6 7))
; [15, 7, 3, 2, 1, -6]
```

### (`array:lsort-asc` \<array>)
Sorts the array in place by the length of its elements in ascending order.
```lisp
(array:lsort-asc (array "fooo" "barsss" "baz" "qx"))
; ["qx", "baz", "fooo", "barsss"]
```

### (`array:lsort-desc` \<array>)
Sorts the array in place by the length of its elements in descending order.
```lisp
(array:lsort-desc (array "fooo" "barsss" "baz" "qx"))
; ["barsss", "fooo", "baz", "qx"]
```

### (`array:at` \<index> \<array>)
Returns the item at the specified index. Negative indices refer to the end of the array.
```lisp
(array:at 1 (array 1 2 3))
; 2
```

### (`array:push` \<array> \<value...>)
Adds one or more values to the end of the array.
```lisp
(array:push (array 1 2) 3 4)
; [1, 2, 3, 4]
```

### (`array:unshift` \<array> \<value...>)
Adds one or more values to the beginning of the array.
```lisp
(array:unshift (array 1 2) 3 4)
; [3, 4, 1, 2]
```

### (`array:pop` \<array>)
Removes the last element from the array and returns it.
```lisp
(array:pop (array 1 2 3))
; 3
```

### (`array:shift` \<array>)
Removes the first element from the array and returns it.
```lisp
(array:shift (array 1 2 3))
; 1
```

### (`array:insert` \<index> \<value> \<array>)
Inserts an item at the given index and shifts the rest of the items to the right. Negative indices refer to the end of the array.
```lisp
(array:insert 1 10 (array 1 2 3))
; [1, 10, 2, 3]

(array:insert -2 10 (array 1 2 3))
; [1, 2, 10, 3]
```

### (`array:first` \<array>)
Returns the first element of the array or `null` if the array is empty.
```lisp
(array:first (array 1 2 3))
; 1
```

### (`array:last` \<array>)
Returns the last element of the array or `null` if the array is empty.
```lisp
(array:last (array 1 2 3))
; 3
```

### (`array:remove` \<array> \<index>)
Removes the item from the array at a given index and returns it, throws an error if the index is out of bounds.
```lisp
(array:remove (array 1 2 3) 1)
; 2
(array:remove (array 1 2 3) 3)
; Error: Index out of bounds: 3
```

### (`array:index` \<array> \<value>)
Returns the index of the item whose value matches or `null` if not found.
```lisp
(array:index (array 1 2 3) 2)
; 1
(array:index (array 1 2 3) 4)
; null
```

### (`array:last-index` \<array> \<value>)
Returns the last index of the item whose value matches or `null` if not found.
```lisp
(array:last-index (array 1 2 3 2) 2)
; 3
```

### (`array:length` \<array>)
Returns the length of the array.
```lisp
(array:length (array 1 2 3))
; 3
```

### (`array:append` \<array> \<array>)
Appends the contents of the given arrays, the original array will be modified.
```lisp
(array:append (array 1 2) (array 3 4))
; [1, 2, 3, 4]
```

### (`array:merge` \<array> \<array>)
Returns a **new** array as the result of merging the given arrays.
```lisp
(array:merge (array 1 2) (array 3 4))
; [1, 2, 3, 4]
```

### (`array:unique` \<array>)
Removes all duplicate values from the array and returns a new array.
```lisp
(array:unique (array 1 2 2 3 3 3))
; [1, 2, 3]
```

### (`array:reverse` \<array>)
Returns a new array with the items in reverse order.
```lisp
(array:reverse (array 1 2 3))
; [3, 2, 1]
```

### (`array:clear` \<array>)
Clears the contents of the array.
```lisp
(array:clear (array 1 2 3))
; []
```

### (`array:clone` \<array> [deep=false])
Creates and returns a replica of the array.
```lisp
(array:clone (array 1 2 3))
; [1, 2, 3]
```

### (`array:flatten` [depth] \<array>)
Returns a flattened array up to the specified depth.
```lisp
(array:flatten (array 1 2 (array 3 4) 5))
; [1, 2, 3, 4, 5]

(array:flatten 1 (array 1 2 (array 3 (array 4 5 6)) 7))
; [1, 2, 3, [4, 5, 6], 7]
```

### (`array:slice` \<start> [length] \<array>)
Returns a slice of the array, starting at the given index and reading the specified number of items,
if the length is not specified the rest of items after the index (inclusive) will be returned.
```lisp
(array:slice 1 2 (array 1 2 3 4 5))
; [2, 3]

(array:slice 2 (array 1 2 3 4 5))
; [3, 4, 5]

(array:slice -3 2 (array 1 2 3 4 5))
; [3, 4]

(array:slice 1 -1 (array 1 2 3 4 5))
; [2, 3, 4]
```

### (`array:slices` \<size> \<array>)
Slices the array in blocks of the given size and returns an array with the resulting slices.
```lisp
(array:slices 4 (array 1 2 3 4 5 6 7 8 9))
; [[1, 2, 3, 4], [5, 6, 7, 8], [9]]
```

<br/><br/>

# Cookies


### (`cookie:exists` \<name>)
Returns `true` if a cookie with the given name exists.
```lisp
(cookie:exists "MyCookie")
; false
```

### (`cookie:set` \<name> \<value> [timeToLive] [domain])
Sets a cookie with the given name and value. Optionally, you can specify the time to live in seconds and the domain.
<br/>NOTE: By default the cookie will be set to never expire.
```lisp
(cookie:set "MyCookie" "hello" 3600)
; null
```

### (`cookie:get` \<name>)
Returns the value of the cookie with the specified name.
```lisp
(cookie:get "MyCookie")
; "hello"
```

<br/><br/>

# Crypto


### (`crypto:hash-list`)
Returns a list of available hash algorithms.
```lisp
(crypto:hash-list)
; ["md2","md4","md5","sha1","sha224", ...]
```

### (`crypto:hash` \<algorithm> \<data>)
Returns the hash of a string (hexadecimal).
```lisp
(crypto:hash "md5" "Hello, World!")
; 65a8e27d8879283831b664bd8b7f0ad4
```

### (`crypto:hash-bin` \<algorithm> \<data>)
Returns the hash of a string (binary).
```lisp
(crypto:hash-bin "md5" "Hello, World!")
; binary data
```

### (`crypto:hmac` \<algorithm> \<secret-key> \<data>)
Returns the HMAC of a string (hexadecimal).
```lisp
(crypto:hmac "sha256" "secret" "Hello, World!")
; fcfaffa7fef86515c7beb6b62d779fa4ccf092f2e61c164376054271252821ff
```

### (`crypto:hmac-binary` \<algorithm> \<secret-key> \<data>)
Returns the HMAC of a string (binary).
```lisp
(crypto:hmac-binary "sha256" "secret" "Hello, World!")
; binary data
```

### (`crypto:unique` \<length> [charset])
Generates a unique code using a cryptographically secure random number generator.
```lisp
(crypto:unique 16)
; If1uIctc_61vluui

(crypto:unique 16 "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@$")
; QjE5SbH8z1OBliBS
```

### (`crypto:equals` \<known-string> \<user-string>)
Timing attack safe string comparison.
```lisp
(crypto:equals "Hello, World!" "Hello, World!")
; true
```

### (`crypto:random-bytes` \<length>)
Generates a pseudo-random string of bytes.
```lisp
(crypto:random-bytes 16)
; (binary data)
```

<br/><br/>

# Database


### (`db:escape` \<value>)
Escapes a value to be used in SQL queries. Uses the connection's driver escape function when necessary.
```lisp
(db:escape "Jack O'Neill")
; 'Jack O''Neill'
```

### (`db:debug` [value])
Sets or returns the current debug flag of the connection.
```lisp
(db:debug true)
; true

(db:debug)
; true
```

### (`db:escape-name` \<value>)
Uses the connection's driver to escape the given value considering it to be a column/table name.
```lisp
(db:escape-name "First Name")
; `First Name`
```

### (`db:scalar` \<query> [...params])
Executes a query and returns a scalar value, that is, first column of first row or `null` if no rows are returned.
```lisp
(db:scalar `SELECT COUNT(*) FROM users WHERE name LIKE ?` "Jack%"))
; 3
```

### (`db:scalars` \<query> [...params])
Executes a query and returns an array with scalars value (all rows, first column).
```lisp
(db:scalars `SELECT name, last_name FROM users WHERE age > ?` 18)
; ["Jack", "Daniel", "Samantha"]
```

### (`db:row` \<query> [...params])
Executes a query and returns a map with the first row.
```lisp
(db:row `SELECT name, last_name FROM users WHERE age >= ?` 21)
; {"name": "Jack", "last_name": "O'Neill"}
```

### (`db:row-values` \<query> [...params])
Executes a query and returns an array with the first row, values only.
```lisp
(db:row-values `SELECT name, last_name FROM users WHERE age >= ?` 21)
; ["Jack", "O'Neill"]
```

### (`db:rows` \<query> [...params])
Executes a query and returns an array with all the resulting rows.
```lisp
(db:rows `SELECT name FROM super_users WHERE age >= ?` 18)
; [{"name": "Jack"}, {"name": "Daniel"}, {"name": "Samantha"}]
```

### (`db:rows-values` \<query> [...params])
Executes a query and returns an array with row values.
```lisp
(db:rows-values `SELECT name, last_name FROM super_users WHERE status=?` "active")
; [["Jack", "O'Neill"], ["Daniel", "Jackson"], ["Samantha", "Carter"]]
```

### (`db:header` \<query> [...params])
Executes a query and returns the header, that is, the field names and the number of rows the query would produce.
```lisp
(db:header `SELECT name, last_name FROM users WHERE status=?` "active")
; {"count": 3, "fields":["name", "last_name"]}
```

### (`db:reader` \<query> [...params])
Executes a query and returns a reader object from which rows can be read incrementally or all at once.
```lisp
(set reader (db:reader `SELECT name FROM super_users WHERE status=?` "active"))
(echo (reader.fields))
; ["name"]

(while (reader.fetch)
    (echo "row #" (+ 1 (reader.index)) ": " (reader.data))
)
; row #1: {"name": "Jack"}
; row #2: {"name": "Daniel"}
; row #3: {"name": "Samantha"}

(reader.close)
```

### (`db:exec` \<query> [...params])
Executes a query and returns a boolean indicating success or failure.
```lisp
(db:exec `DELETE FROM users WHERE status=?` "inactive"))
; true
```

### (`db:update` \<table-name> \<condition> \<fields>)
Executes a row update operation and returns boolean indicating success or failure.
```lisp
(db:update "users" "id=1" (& name "Jack" last_name "O'Neill"))
; true

(db:update "users" (& id 1) (& name "Jack"))
; true
```

### (`db:insert` \<table-name> \<fields>)
Executes a row insert operation and returns the ID of the newly inserted row or `null` if the operation failed.
```lisp
(db:insert `users` (& name "Daniel" last_name "Jackson"))
; 3
```

### (`db:get` \<table-name> \<condition>)
Returns a single row matching the specified condition.
```lisp
(db:get "users" "id=1")
; {"id": 1, "name": "Jack", "last_name": "O'Neill"}

(db:get "users" (& id 3))
; {"id": 2, "name": "Samantha", "last_name": "Carter"}
```

### (`db:delete` \<table-name> \<condition>)
Deletes one or more rows from a table and returns a boolean indicating success or failure.
```lisp
(db:delete "users" "user_id=1")
; true

(db:delete "users" (& user_id 3))
; true
```

### (`db:last-insert-id`)
Returns the ID of the row created by the last insert operation.
```lisp
(db:last-insert-id)
; 3
```

### (`db:affected-rows`)
Returns the number of affected rows by the last update operation.
```lisp
(db:affected-rows)
; 45
```

### (`db:open` \<config>)
Opens a new connection, sets it as active and returns the database handle, use it only when managing multiple
connections to different database servers. If only one is used (the default one) this is not necessary.
```lisp
(db:open { server "localhost" user "main" password "mypwd" database "test" driver "mysql" trace false })
; [Rose\Data\Connection]
```

### (`db:close` \<connection>)
Closes the specified connection. If the provided connection is the currently active one, it will be closed and the
default connection will be activated (if any).

### (`db:conn` \<connection>)<br/>(`db:conn`)
Sets or returns the active conection. Should be used only if you're managing multiple connections.
<br/>Pass `null` as parameter to use the default connection.

<br/><br/>

# DateTime
Provides functions to manipulate date and time.

### (`datetime:now` [targetTimezone])
Returns the current date and time.
```lisp
(datetime:now)
; 2024-03-23 02:19:49

(datetime:now "America/New_York")
; 2024-03-23 04:20:05

(datetime:now "GMT-7.5")
; 2024-03-23 01:20:39
```

### (`datetime:now-int`)
@deprecated Use `datetime:int` or `datetime:float` instead.
<br/>Returns the current date and time as a UNIX timestamp in UTC.
```lisp
(datetime:now-int)
; 1711182138
```

### (`datetime:millis`)
Returns the current datetime as a UNIX timestamp in milliseconds.
```lisp
(datetime:millis)
; 1711182672943
```

### (`datetime:parse` \<input> [targetTimezone] [sourceTimezone])
Parses a date and time string. Assumes source is in local timezone (LTZ) if no `sourceTimezone` specified. Note that the
default `targetTimezone` is the one configured in the `timezone` setting of the `Locale` configuration section.
```lisp
(datetime:parse "2024-03-23 02:19:49")
; 2024-03-23 02:19:49

(datetime "2024-03-23 02:19:49" "America/New_York")
; 2024-03-23 04:19:49
```

### (`datetime:int` [\<input>])
Parses a date and time string (or uses current time is none provided) and returns a UNIX timestamp.
```lisp
(datetime:int "2024-03-23 02:19:49")
; 1711181989
```

### (`datetime:float` [\<input>])
Parses a date and time string (or uses current time is none provided) and returns a UNIX timestamp.
```lisp
(datetime:float "2024-03-23 02:19:49.500")
; 1711181989.5
```

### (`datetime:sub` \<value-A> \<value-B> [unit])
Returns the subtraction of two datetime in a given unit (`A` minus `B`). Defaults to seconds.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:sub "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; -3600
```

### (`datetime:diff` \<value-A> \<value-B> [unit])
Returns the absolute difference between two datetime in a given unit (defaults to seconds).
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:diff "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; 3600
```

### (`datetime:add` \<input> \<delta> [unit])
Returns the addition of a given delta value in a given unit (defaults to seconds) to a datetime.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:add "2024-03-23 02:19:49" 3600 "SECOND")
; 2024-03-23 03:19:49
```

### (`datetime:date` \<input>)
Returns the date part of a datetime.
```lisp
(datetime:date "2024-03-23 02:19:49")
; 2024-03-23
```

### (`datetime:time` \<input> [seconds=false])
Returns the time part of a datetime (only hours and minutes).
```lisp
(datetime:time "2024-03-23 02:19:49")
; 02:19
```

### (`datetime:format` \<input> \<format>)
Formats a date and time string.
```lisp
(datetime:format "2024-03-23 02:19:49" "Year: %Y, Month: %m, Day: %d & Time: %H:%M:%S")
; Year: 2024, Month: 03, Day: 23 & Time: 02:19:49
```

### (`datetime:tz` [timezone])
Returns or sets the global timezone.
```lisp
(datetime:tz)
; America/New_York
```

<br/><br/>

# Image


### (`image:load` \<path>)
Loads an image from a file.

### (`image:save` \<image> [path=null] [format=JPG|GIF|PNG] [quality=95])
Saves the image to the given path. If no target specified the filename used in `load` will be used.

### (`image:dump` \<image> [format=JPG|GIF|PNG] [quality=95])
Outputs the image to the browser. This method will send header information, therefore it is required
that no data had been output when called.

### (`image:data` \<image> [format=JPG|GIF|PNG] [mode=BINARY|DATA_URI|BASE64] [quality=95])
Returns the binary image data. The following output modes are supported: `BINARY`, `DATA_URI`, and `BASE64`.

### (`image:width` \<image> \<newWidth> [keepRatio=true])<br/>(`image:width` \<image>)
Returns or sets the width of the image. If the `newWidth` parameter is not `null` the image will be horizontally
resized to the given width, and the height will be adjusted if the `keepRatio` parameter is set to true.

### (`image:height` \<image> \<newHeight> [keepRatio=true])<br/>(`image:height` \<image>)
Returns or sets the height of the image. If the `newHeight` parameter is not `null` the image will be vertically
resized to the given height, and the width will be adjusted if the `keepRatio` parameter is set to true.

### (`image:resize` \<image> \<newWidth> \<newHeight> [rewriteOriginal=true])
Resizes the image to the given width and height.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

### (`image:scale` \<image> \<scaleX> [scaleY=null] [rewriteOriginal=true])
Scales the image by the given the width and height factors. Each factor must be a real number between 0 and 1 (inclusive),
if parameter `scaleY` is not set, the same value as `scaleX` will be used.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

### (`image:fit` \<image> \<newWidth> \<newHeight> [rewriteOriginal=false])
Resizes the image to fit in the given area, maintains the aspect ratio.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

### (`image:cut` \<image> \<newWidth> \<newHeight> [startX] [startY])
Cuts a portion of the image and returns a new image object. Set the start coordinates to `null` to get a
centered cut. If the size parameters are `null` they will be set to the size of the image.

### (`image:crop` \<image> \<newWidth> \<newHeight> [startX] [startY])
Crops to a portion of the image. This is similar to cutting a portion of the image and replacing the original
by the portion.

### (`image:smartcut` \<image> \<newWidth> \<newHeight> \<onTooWide> \<onTooTall> [rewriteOriginal=false])
Cuts a portion of the image smartly by detecting if scaling is needed and scale accordingly. The `onTooWide` and `onTooTall`
parameters indicate how to cut if the image is either too wide or too tall respectively. Valid values for these parameters are
"CENTER", "LEFT", "RIGHT", "TOP", and "BOTTOM".
<br/>
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

<br/><br/>

# Map


### (`map:new` [key value...])<br/>(`&` [key value...])<br/>{ key value... }
Constructs a map with the given key-value pairs. Note that the second form (&) is legacy from previous syntax.
```lisp
(map:new 'a' 1 'b' 2)
; {"a":1,"b":2}

(& "name" "Jenny" "age" 25)
; {"name":"Jenny","age":25}

{ name "Jon" age 36 }
; {"name":"Jon","age":36}
```

### (`map:sort-asc` \<map>)
Sorts the map in place by value in ascending order.
```lisp
(map:sort-asc (map:new 'b' 2 'a' 1))
; {"a": 1, "b": 2}
```

### (`map:sort-desc` \<map>)
Sorts the map in place by value in descending order.
```lisp
(map:sort-desc (map:new 'b' 2 'a' 1))
; {"b": 2, "a": 1}
```

### (`map:ksort-asc` \<map>)
Sorts the map in place by key in ascending order.
```lisp
(map:ksort-asc (map:new 'b' 5 'a' 10))
; {"a": 10, "b": 5}
```

### (`map:ksort-desc` \<map>)
Sorts the map in place by key in descending order.
```lisp
(map:ksort-desc (map:new 'b' 5 'a' 10))
; {"b": 5, "a": 10}
```

### (`map:keys` \<map>)
Returns the keys of the map.
```lisp
(map:keys (map:new 'a' 1 'b' 2))
; ["a", "b"]
```

### (`map:values` \<map>)
Returns the values of the map.
```lisp
(map:values (map:new 'a' 1 'b' 2))
; [1, 2]
```

### (`map:set` \<map> [key value...])
Sets one or more key-value pairs in the map.
```lisp
(map:set (map:new 'a' 1) 'b' 2 'x' 15)
; {"a": 1, "b": 2, "x": 15}
```

### (`map:get` \<map> \<key>)
Returns the value of the given key in the map.
```lisp
(map:get (map:new 'a' 1 'b' 2) 'b')
; 2
```

### (`map:has` \<map> \<key>)
Returns `true` if the map has the given key, `false` otherwise.
```lisp
(map:has (map:new 'a' 1 'b' 2) 'b')
; true
```

### (`map:del` \<map> \<key>)
Removes the given key from the map and returns the removed value.
```lisp
(map:del (map:new 'a' 1 'b' 112) 'b')
; 112
```

### (`map:key` \<map> \<value>)
Returns the key of the element whose value matches or `null` if not found.
```lisp
(map:key (map:new 'a' 1 'b' 2) 2)
; b
```

### (`map:len` \<map>)
Returns the length of the Map.
```lisp
(map:length (map:new 'a' 1 'b' 2))
; 2
```

### (`map:assign` \<output-map> \<map...>)
Merges one or more maps into the first.
```lisp
(map:assign (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
; {"a": 1, "b": 2, "c": 3}
```

### (`map:merge` \<map...>)
Merges one or more maps into a new map.
```lisp
(map:merge (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
; {"a": 1, "b": 2, "c": 3}
```

### (`map:clear` \<map>)
Clears the contents of the map.
```lisp
(map:clear (map:new 'a' 1 'b' 2))
; {}
```

### (`map:diff` \<map1> \<map2>)
Returns the difference between two maps.
```lisp
(map:diff { a 1 b 2 } { a 2 b 2 c 3 })
; {"a":[1,2], "c":[null,3]}
```

<br/><br/>

# OpenSSL


### (`openssl:version`)
Returns the version of the OpenSSL library.
```lisp
(openssl:version)
; "OpenSSL 3.0.13 30 Jan 2024"
```

### (`pem:encode` \<label> \<data>)
Wraps the given buffer in a PEM encoded block with the specified label.

### (`openssl:curves`)
Returns a list of supported curves.
```lisp
(openssl:curves)
; ["prime192v1","secp224r1","prime256v1",...]
```

### (`openssl:ciphers`)
Returns a list of supported ciphers.
```lisp
(openssl:ciphers)
; ["prime192v1","secp224r1","prime256v1",...]
```

### (`openssl:random-bytes` \<length>)
Generates a pseudo-random string of bytes.
```lisp
(openssl:random-bytes 16)
; (binary data)
```

### (`openssl:create` \<DSA|DH|RSA|EC> [curve-name] [bits])
Creates a new private key of the specified type. Returns `pkey` object. Note that when using EC keys, the curve name is
required, see `openssl:curves` for a list of supported curves.
```lisp
(openssl:create "EC" "prime256v1")
; (pkey)
```

### (`openssl:bits` \<pkey>)
Returns the number of bits in the key.
```lisp
(openssl:bits (pkey))
; 4096
```

### (`openssl:export-private` \<pkey>)
Export the private key as a PEM encoded string.
```lisp
(openssl:export-private (pkey))
; "-----BEGIN ...
```

### (`openssl:export-public` \<pkey>)
Export the public key as a PEM encoded string.
```lisp
(openssl:export-public (pkey))
; "-----BEGIN ...
```

### (`openssl:import-private` \<pem-data>)
Loads a private key (PEM format) from the specified data buffer.
```lisp
(openssl:import-private "-----BEGIN ...")
; (pkey)
```

### (`openssl:import-public` \<pem-data>)
Loads a public key (PEM format) from the specified data buffer.
```lisp
(openssl:import-public "-----BEGIN ...")
; (pkey)
```

### (`openssl:error`)
Returns the last error message (if any) or empty string.
```lisp
(openssl:error)
; "error:0D07207B:asn1 encoding routines:ASN1_get_object:header too long"
```

### (`openssl:sign` \<private-key> \<algorithm> \<data>)
Signs a data block using a private key and returns a signature in DER format.
<br/>Supported signing algorithms are: DSS1, SHA1, SHA224, SHA256, SHA384, SHA512, RMD160, MD5, MD4, and MD2.
```lisp
(openssl:sign (priv-key) "SHA256" "hello")
; (binary data)
```

### (`openssl:verify` \<public-key> \<algorithm> \<signature> \<data>)
Verifies a signature (DER format) of a data block using a public key. See `openssl:sign` for supported signing algorithms.
```lisp
(openssl:verify (pub-key) "SHA256" (signature) "hello")
; true
```

### (`openssl:public-encrypt` \<public-key> \<data>)
Encrypts a data block with a public key. Use `openssl:private-decrypt` to decrypt the data.

### (`openssl:private-decrypt` \<private-key> \<encrypted-data>)
Decrypts a data block with a private key. Use `openssl:public-encrypt` to encrypt the data.

### (`openssl:private-encrypt` \<private-key> \<data>)
Encrypts a data block with a private key. Use `openssl:public-decrypt` to decrypt the data.

### (`openssl:public-decrypt` \<public-key> \<encrypted-data>)
Decrypts a data block with a public key. Use `openssl:private-encrypt` to encrypt the data.

### (`openssl:derive` \<private-key> \<public-key> [key-length])
Generates a shared secret for public value of remote and local DH or ECDH key.
```lisp
(openssl:derive (priv-key) (pub-key))
; (binary data)
```

### (`openssl:encrypt` \<cipher-algorithm> \<secret> \<iv> \<data>)

### (`der:extract` \<type='int'|'bits'|'octets'> \<der-string|pem-string> [\<int-size=0>])
Extracts fields from a DER encoded string.

### (`der:get` \<pem-string>)
Converts a PEM encoded key to DER format.

### (`der:parse` \<der-string|pem-string>)
Parses a DER encoded data and returns a map with 'int', 'bits', 'octets' fields.

<br/><br/>

# HTTP Requests


### (`request:get` \<url> [fields...])
Executes a GET request and returns the response data.
```lisp
(request:get "http://example.com/api/currentTime")
; 2024-12-31T23:59:59
```

### (`request:head` \<url> [fields...])
Executes a HEAD request and returns the HTTP status code. Response headers will be available using `request:response-headers`.
```lisp
(request:head "http://example.com/api/currentTime")
; 200
```

### (`request:post` \<url> [fields...])
Executes a POST request and returns the response data.
```lisp
(request:post "http://example.com/api/login" (& "username" "admin" "password" "admin"))
; { "token": "eyJhbGciOiJIUzI" }
```

### (`request:put` \<url> [fields...])
Executes a PUT request and returns the response data.
```lisp
(request:put "http://example.com/api/user/1" (& "name" "John Doe"))
; { "id": 1, "name": "John Doe" }
```

### (`request:delete` \<url> [fields...])
Executes a DELETE request and returns the response data.
```lisp
(request:delete "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

### (`request:patch` \<url> [fields...])
Executes a PATCH request and returns the response data.
```lisp
(request:patch "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

### (`request:fetch` [method] \<url> [fields...])
Executes a fetch request using the specified method and returns a parsed JSON response. Default method is `GET`.
```lisp
(request:fetch "http://example.com/api/currentTime")
; { "currentTime": "2024-12-31T23:59:59" }
```

### (`request:headers` [header-line|array])
Returns the current headers or sets one or more headers for the next request.
```lisp
(request:headers "Authorization: Bearer MyToken")
; true
(request:headers)
; ["Authorization: Bearer MyToken"]
```

### (`request:response-headers` [header])
Returns the response headers of the last request or a single header (if exists).
```lisp
(request:response-headers)
; { "content-type": "application/json", "content-length": "123" }

(request:response-headers "content-type")
; application/json
```

### (`request:debug` \<value>)
Enables or disables request debugging. When enabled request data will be output to the log file.
```lisp
(request:debug true)
; true
```

### (`request:verify` \<value>)
Enables or disables SSL verification for requests.
```lisp
(request:verify false)
; true
```

### (`request:auth` "basic" \<username> \<password>)<br/>(`request:auth` "basic" \<username>)<br/>(`request:auth` "bearer" \<token>)<br/>(`request:auth` \<token>)<br/>(`request:auth` false)
Sets the HTTP Authorization header for the next request.
```lisp
(request:auth "basic" "admin" "admin")
; true
```

### (`request:status`)
Returns the HTTP status code of the last request.
```lisp
(request:status)
; 200
```

### (`request:error`)
Returns the last error message.
```lisp
(request:error)
; Could not resolve host
```

### (`request:content-type`)
Returns the content-type of the last request. Shorthand for `(request:headers "content-type")` without the charset.
```lisp
(request:content-type)
; text/html
```

### (`request:data`)
Returns the raw data returned by the last request.
```lisp
(request:data)
; HelloWorld
```

### (`request:clear`)
Clears the current headers, response headers and response data.

### (`request:output-handler` \<func>)
Sets the output handler for the next request.
```lisp
(request:output-handler (fn data (echo (data))))
; true
```

### (`request:output-file` \<file-path>)
Sets the output file for the next request.
```lisp
(request:output-file "output.txt")
; true
```

### (`request:input-handler` \<func>)
Sets the input handler for the next request.
```lisp
(request:input-handler (fn max_bytes (ret "....")))
; true
```

### (`request:input-file` \<file-path>)
Sets the input file for the next request.
```lisp
(request:input-file "sample.jpg")
; true
```

### (`request:progress-handler` \<func>)
Sets the progress handler for the next request.
```lisp
(request:progress-handler (fn total_bytes curr_bytes ...))
; true
```

<br/><br/>

# Session


### (`session`)
Object with the current session data.
```lisp
(session)
; {"key1": "value1", "key2": "value2"}
```

### (`session:open` [createSession=true])
Attempts to open an existing session or creates a new one (if `createSession` is `true`). The cookie name and other
configuration fields are obtained from the `Session` configuration section.
```lisp
(session:open)
; true
```

### (`session:close` [activityOnly=false])
Closes the current session and writes the session data to permanent storage (file system or database). If the `activityOnly` 
parameter is `true` only the session's last activity field will be written to storage.

### (`session:load` [createSession=true])
Attempts to open an existing session and if exists its data will be loaded, the session will be immediately closed afterwards and only the
`last_activity` field will be updated. This is useful to prevent session blocking. Use `session:save` to save the session data.
```lisp
(session:load)
; true
```

### (`session:save`)
Attempts to save the data to the session if it exists.
```lisp
(session:save)
; true
```

### (`session:destroy`)
Destroys the current session, removes all session data including the session's cookie.

### (`session:clear`)
Clears the session data and keeps the same cookie name.

### (`session:name`)
Returns the name of the session cookie, default ones comes from the `Session` configuration section.
```lisp
(session:name)
; "MySession"
```

### (`session:id` [newSessionID])<br/>(`session:id`)
Returns or sets the current session ID.
```lisp
(session:id)
; "oldSessionID"

(session:id "newSessionID")
; "newSessionID"
```

### (`session:is-open`)
Returns boolean indicating if the session is open.
```lisp
(session:is-open)
; true
```

<br/><br/>

# Server-Sent Events (SSE)


### (`sse:init`)
Initializes server-sent events (SSE) by setting several HTTP headers in the response and configuring the Gateway
to persist and disable any kind of output buffering.
```lisp
(sse:init)
; Content-Type: text/event-stream; charset=utf-8
; Transfer-Encoding: identity
; Content-Encoding: identity
; Cache-Control: no-store
; X-Accel-Buffering: no
```

### (`sse:send` [event-name] \<data>)
Sends the specified data to the browser as a server-sent event. If no event name is specified, the default event
name `message` will be used.
```lisp
(sse:send "message" "Hello, World!")
; event: message
; data: Hello, World!

(sse:send "info" (& list (# 1 2 3)))
; event: info
; data: {"list":[1,2,3]}
```

### (`sse:alive`)
Sends a comment line `:alive` to the browser to keep the connection alive if the last message sent was more than
30 seconds ago. Returns `false` if the connection was already closed by the browser.
```lisp
(while (sse:alive)
    ; Do something ...
    (sys:sleep 1)
)
```

<br/><br/>

# Utilities


### (`env:get-all`)
Returns all the environment variables.
```lisp
(env:get-all)
; {"HOME":"/home/user","PATH":"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"}
```

### (`env:get` \<name>)
Returns an environment variable or `null` if not found.
```lisp
(env:get "HOME")
; "/home/user"
```

### (`env:set` \<value>)
Sets an environment variable.
```lisp
(env:set "HOME=/home/user")
; true
```

### (`base64:encode` \<value>)
Encodes a value to base64.
```lisp
(base64:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ=="
```

### (`base64:decode` \<value>)
Decodes a base64 value.
```lisp
(base64:decode "SGVsbG8sIFdvcmxkIQ==")
; "Hello, World!"
```

### (`base64u:encode` \<value>)
Encodes a value to base64 URL-safe format, that is a base64 string with `+` as `-`, `/` as `_` and without any `=`.
```lisp
(base64u:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ"
```

### (`base64u:decode` \<value>)
Decodes a base64 URL-safe value.
```lisp
(base64u:decode "SGVsbG8sIFdvcmxkIQ")
; "Hello, World!"
```

### (`hex:encode` \<string>)
Encodes a string into hexadecimal format.
```lisp
(hex:encode "Hi!")
; "486921"
```

### (`hex:decode` \<string>)
Decodes a hexadecimal string.
```lisp
(hex:decode "486921")
; "Hi!"
```

### (`url:encode` \<value>)
Encodes a value to be used in a URL.
```lisp
(url:encode "Hello = World")
; "Hello%20%3D%20World"
```

### (`url:decode` \<value>)
Decodes a URL-encoded value.
```lisp
(url:decode "Hello%20%3D%20World")
; "Hello = World"
```

### (`url-query:str` \<fields>)
Converts a map to a URL query string.
```lisp
(url-query:str (& name "John" "age" 35))
; "name=John&age=35"
```

### (`html-text:encode` \<value>)
Encodes a value to be used in HTML text.
```lisp
(html-text:encode "<Hello>")
; "&lt;Hello&gt;"
```

### (`html-text:decode` \<value>)
Decodes a value encoded for HTML text.
```lisp
(html-text:decode "&lt;Hello&gt;")
; "<Hello>"
```

### (`gz:compress` \<string>)
Compresses a string using the Gzip algorithm.
```lisp
(gz:compress "Hi!")
; (binary data)
```

### (`gz:decompress` \<string>)
Decompresses a string compressed using the Gzip algorithm.
```lisp
(gz:decompress (gz:compress "Hi!"))
; "Hi!"
```

### (`gz:deflate` \<string>)
Compresses a string using the Deflate algorithm.
```lisp
(gz:deflate "Hi!")
; (binary data)
```

### (`gz:inflate` \<string>)
Inflates (decompresses) a string compressed using the Deflate algorithm.
```lisp
(gz:inflate (gz:deflate "Hi!"))
; "Hi!"
```

### (`json:str` \<value>)
Converts a value to a JSON string.
```lisp
(json:str (& name "John" "age" 35))
; {"name":"John","age":35}
```

### (`json:dump` \<value>)
Converts a value to a JSON string with indentation (pretty-print). Useful to debug nested structures.
```lisp
(json:dump (# 1 2 3))
; [1,2,3]
```

### (`json:parse` \<string>)
Parses a JSON string and returns the value.
```lisp
(json:parse "[ 1, 2, 3 ]")
; [1,2,3]
```

### (`utils:uuid`)
Generates a random UUID (Universally Unique Identifier) version 4.
```lisp
(utils:uuid)
; "550e8400-e29b-41d4-a716-446655440000"
```

### (`xml:parse` \<xml>)
Parses an XML string and returns a map containing XML node information fields `tagName`, `attributes`,
`children` and `textContent`.
```lisp
(xml:parse "<root><item id='1'>Item 1</item><item id='2'>Item 2</item></root>")
; {
;     "tagName": "root",
;     "attributes": {},
;     "children": [
;         {
;             "tagName": "item",
;             "attributes": {
;                 "id": "1"
;             },
;             "children": [],
;             "textContent": "Item 1"
;         },
;         {
;             "tagName": "item",
;             "attributes": {
;                 "id": "2"
;             },
;             "children": [],
;             "textContent": "Item 2"
;         }
;     ],
;     "textContent": ""
; }
```

### (`xml:simplify` \<xml-node>)
Simplifies an XML node into a more easy to traverse structure. Any node with no children and no attributes
will be converted to a string with its text content. Nodes with children will be converted to a map with the
tag name as key and the children as value. If a node has attributes, they will be stored in a `$` key.
```lisp
(xml:simplify (xml:parse "<root name=\"Test\"><item>Item 1</item><item>Item 2</item></root>"))
; {
;    "root": {
;        "$": {
;            "name": "Test"
;        },
;        "item": [
;            "Item 1",
;            "Item 2"
;        ]
;    }
; 
```

### (`html:encode` \<data>)
Converts an array or map into an HTML table.
```lisp
(html:encode (# (& "Name" "John" "Age" 35) (& "Name" "Jane" "Age" 25)))
; HTML table with two rows and two columns
```

### (`sys:version`)
Returns the version of the framework.
```lisp
(sys:version)
; 5.0.1
```

### (`sys:exit` [errorlevel])
Exits the program with the given error level.
```lisp
(sys:exit 0)
```

### (`sys:sleep` \<seconds>)
Sleeps for the given number of seconds.
```lisp
(sys:sleep 0.5)
; true
```

### (`sys:shell` \<command>)
Executes a shell command and returns the complete output as a string.
```lisp
(sys:shell "ls -l")
; "total 0\n-rw-r--r-- 1 user user 0 Jan  1 00:00 file.txt\n"
```

### (`sys:exec` \<command>)
Executes a command and returns the exit code.
```lisp
(sys:exec "ls -l")
; 0
```

### (`sys:gc`)
Runs the garbage collector.
```lisp
(sys:gc)
; 1
```

### (`sys:peak-memory`)
Returns the memory peak usage in megabytes.
```lisp
(sys:peak-memory)
; 4.58
```

### (`strings`)
Object used to access language strings. The strings are stored in the `strings` directory in the root of the project.
```lisp
(strings.messages)
; (value of the `messages` file located in `strings/messages.conf`)

(strings.messages.welcome)
; (value of the `welcome` key in the `strings/messages.conf` file)

(strings.@messages.welcome)
; (value of the `welcome` key in the `strings/en/messages.conf` file)
```

### (`strings:lang` [lang])
Returns or sets the current language for the strings extension. The folder should exist in the `strings` directory,
otherwise an error will be thrown.
```lisp
(strings:lang)
; "en"
(strings:lang "xx")
; Error: Language code `xx` is not supported
```

### (`strings:get` [lang])
Returns a string given the path. If the target string is not found then the given path will be returned as a placeholder.
```lisp
(strings:get "messages.welcome")
; "Welcome!"

(strings:get "@messages.welcome")
; "@messages.welcome"
```

<br/><br/>

# Gateway
Provides an interface between clients and the system. No client can have access to the system without passing first through the Gateway.

### (`gateway.request`)
Map with the request parameters from both GET and POST methods.
```lisp
(gateway.request)
; {"name": "John"}
```

### (`gateway.server`)
Map with the server parameters sent via CGI.
```lisp
(gateway.server)
; {"SERVER_NAME": "localhost"}
```

### (`gateway.headers`)
Map with the HTTP headers sent via CGI.
```lisp
(gateway.headers)
; {"HOST": "localhost", "X_KEY": "12345"}
```

### (`gateway.cookies`)
Map with the cookies sent by the client.
```lisp
(gateway.cookies)
; {"session": "123"}
```

### (`gateway.ep`)
Full URL address to the entry point of the active service. Never ends with slash.
```lisp
(gateway.ep)
; "http://localhost"
```

### (`gateway.serverName`)
Server name obtained from the CGI fields or from the `server_name` field in the `Gateway` configuration section.
```lisp
(gateway.serverName)
; "localhost"
```

### (`gateway.method`)
HTTP method used to access the gateway, will always be in uppercase.
```lisp
(gateway.method)
; "GET"
```

### (`gateway.remoteAddress`)<br/>(`gateway.remotePort`)
Remote address (and port) of the client.
```lisp
(gateway.remoteAddress)
; "127.0.0.1"

(gateway.remotePort)
; 12873
```

### (`gateway.root`)
Relative URL root where the index file is found. Usually it is "/".
```lisp
(gateway.root)
; "/"
```

### (`gateway.fsroot`)
Local file system root where the index file is found.
```lisp
(gateway.fsroot)
; "/var/www/html"
```

### (`gateway.secure`)
Indicates if we're on a secure context (HTTPS).
```lisp
(gateway.secure)
; true
```

### (`gateway.input`)
Object contaning information about the request body received.
```lisp
(gateway.input)
; {"contentType": "application/json", "size": 16, "path": "/tmp/1f29g87h12"}
```

### (`gateway.body`)
Contains a parsed object if the content-type is `application/json`. For other content types, it will be `null` and the actual data can
be read from the file specified in the `path` field of the `input` object.
```lisp
(gateway.body)
; {"name": "John"}
```

### (`gateway:status` \<code>)
Sets the HTTP status code to be sent to the client.
```lisp
(gateway:status 404)
; true
```

### (`gateway:header` \<header-line...>)
Sets a header in the current HTTP response.
```lisp
(gateway:header "Content-Type: application/json")
; true
```

### (`gateway:redirect` \<url>)
Redirects the client to the specified URL by setting the `Location` header and exiting immediately.

### (`gateway:flush`)
Flushes all output buffers and prepares for immediate mode (unbuffered output).
```lisp
(gateway:flush)
; true
```

### (`gateway:persistent`)
Configures the system to use persistent execution mode in which the script will continue to run indefinitely for as 
long as the server allows, even if the client connection is lost.
```lisp
(gateway:persistent)
; true
```

### (`gateway:timeout` \<seconds>)
Sets the maximum execution time of the current operation. Use `NEVER` to disable the timeout.
```lisp
(gateway:timeout 30)
; true
```

### (`gateway:return` [\<status>] [\<response>])
Sends a response to the client and exits immediately.
```lisp
(gateway:return 200 "Hello, World!")
; Client will receive:
; Hello, World!
```

### (`gateway:continue` [\<status>] [\<response>])
Sends a response to the client, closes the connection and continues execution. Further output
for the client will be ignored.
```lisp
(gateway:continue 200 "Hello, World!")
; Client will receive:
; Hello, World!
```

<br/><br/>

# Directory


### (`dir:create` \<path>)
Creates a directory and all its parent directories (if needed). Returns boolean.
```lisp
(dir:create "/tmp/test")
; true
```

### (`dir:files` \<path> [regex-pattern])
Returns an array with file entries in the directory. Each entry is a map with keys `name` and `path`.
```lisp
(dir:files "/home")
; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
```

### (`dir:dirs` \<path> [regex-pattern])
Returns an array with directory entries in the directory. Each entry is a map with keys `name` and `path`.
```lisp
(dir:dirs "/home")
; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
```

### (`dir:entries` \<path> [regex-pattern])
Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
and directory entries each of which is a map with keys `name` and `path`.
```lisp
(dir:entries "/home")
; {
;    name: "home", path: "/home", 
;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
; }
```

### (`dir:files-recursive` \<path> [regex-pattern])
Returns an array with file entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
```lisp
(dir:files-recursive "/home")
; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
```

### (`dir:dirs-recursive` \<path> [regex-pattern])
Returns an array with directory entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
```lisp
(dir:dirs-recursive "/home")
; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
```

### (`dir:entries-recursive` \<path> [regex-pattern])
Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
and directory entries in the folder and all its subfolders. Each entry is a map with keys `name` and `path`.
```lisp
(dir:entries-recursive "/home")
; {
;    name: "home", path: "/home",
;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
; }
```

### (`dir:remove` \<path>)
Removes a directory (must be empty) returns `true` if success.
```lisp
(dir:remove "/tmp/test")
; true
```

### (`dir:remove-recursive` \<path>)
Removes a directory recursively and returns `true` if success.
```lisp
(dir:remove-recursive "/tmp/test")
; true
```

### (`dir:rmdir` \<path>)
Removes a directory (must be empty) without any checks. Returns `true` if success.
```lisp
(dir:rmdir "/tmp/test")
; true
```

### (`dir:copy` \<source> \<destination> [recursive=true] [overwrite=true])
Copies all files (and directories if `recursive` is set) from the `source` to the `destination` directories. If
`overwrite` is true the destination files will be overwritten.
```lisp
(dir:copy "/tmp/test" "/tmp/test2")
; true
```

<br/><br/>

# File


### (`file:size` \<path>)
Returns the size of the file or `null` if the file does not exist.
```lisp
(file:size "/var/www/image.jpg")
; 1024
```

### (`file:dump` \<path>)
Dumps the file contents to the standard output.
```lisp
(file:dump "/var/www/image.jpg")
; Image data
```

### (`file:mtime` \<path>)
Returns the modification time of the file as a datetime string (LTZ).
```lisp
(file:mtime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

### (`file:atime` \<path>)
Returns the last access time of the file as a datetime string (LTZ).
```lisp
(file:atime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

### (`file:touch` \<path> [datetime])
Sets the last modified time of a file (UTC). Datetime can be a datetime object, string or a unix timestamp.
```lisp
(file:touch "/var/www/image.jpg" "2024-03-24 03:36:18")
; true
```

### (`file:read` \<path>)
Reads and returns the contents of the file.
```lisp
(file:read "/var/www/test.txt")
; "Hello, World!"
```

### (`file:write` \<path> \<contents>)
Writes the contents to the file.
```lisp
(file:write "/var/www/test.txt" "Hello, World!")
; true
```

### (`file:append` \<path> \<contents>)
Appends the given contents to the file.
```lisp
(file:append "/var/www/test.txt" "Hello, World!")
; true
```

### (`file:remove` \<path>)
Deletes a file, returns `true` if success.
```lisp
(file:remove "/var/www/test.txt")
; true
```

### (`file:unlink` \<path>)
Deletes a file. Does not check anything.
```lisp
(file:unlink "/var/www/test.txt")
; true
```

### (`file:copy` \<source> \<target> [stream=false])
Copies a file (overwrites if exists) to the given target, which can be directory or file. Use the `stream` flag
to copy the file using manual streaming.
```lisp
(file:copy "/var/www/image.jpg" "/var/www/images")
; true
```

### (`file:create` \<path>)
Creates a file, returns `true` if the file was created, or `false` if an error occurred.
```lisp
(file:create "/var/www/test.txt")
; true
```

### (`stream:open` \<path> [mode='r'])
Opens a file for reading, writing or appending, and returns a data stream.
```lisp
(stream:open "test.txt" "w")
; (data-stream)
```

### (`stream:close` \<data-stream>)
Close a file data stream.
```lisp
(stream:close (stream:open "test.txt" "w"))
; true
```

### (`stream:write` \<data-stream> \<data>)
Writes data to a file data stream.
```lisp
(stream:write (fh) "Hello, World!")
; true
```

### (`stream:read` \<data-stream> \<length>)
Reads and returns up to length bytes from the file data stream. Returns empty string at EOF or `null` on error.
```lisp
(stream:read (fh) 1024)
; "Hello, World!"
```

<br/><br/>

# Path


### (`path:fsroot`)
Returns the fsroot, the path from which the script is executed.
```lisp
(path:fsroot)
; /var/www/html
```

### (`path:cwd`)
Returns the current working directory.
```lisp
(path:cwd)
; /var/www/html
```

### (`path:basename` \<path>)
Returns the base name in the path (includes extension).
```lisp
(path:basename '/var/www/html/index.html')
; index.html
```

### (`path:extname` \<path>)
Returns the extension name.
```lisp
(path:extname '/var/www/html/index.html')
; .html
```

### (`path:name` \<path>)
Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
```lisp
(path:name '/var/www/html/index.html')
; index
```

### (`path:normalize` \<path>)
Normalizes the separator in the given path.
```lisp
(path:normalize '/var\\www/html\\index.html')
; /var/www/html/index.html
```

### (`path:dirname` \<path>)
Returns the directory name.
```lisp
(path:dirname '/var/www/html/index.html')
; /var/www/html
```

### (`path:resolve` \<path>)
Returns the fully resolved path.
```lisp
(path:resolve './index.html')
; /var/www/html/index.html
```

### (`path:append` \<path> \<items...>)
Appends the given items to the specified path.
```lisp
(path:append '/var/www/html' 'index.html')
; /var/www/html/index.html
```

### (`path:is-file` \<path>)
Returns `true` if the path points to a file.
```lisp
(path:is-file '/var/www/html/index.html')
; true
```

### (`path:is-dir` \<path>)
Returns `true` if the path points to a directory.
```lisp
(path:is-dir '/var/www/html')
; true
```

### (`path:is-link` \<path>)
Returns `true` if the path points to a symbolic link.
```lisp
(path:is-link '/var/www/html/index.html')
; false
```

### (`path:exists` \<path>)
Returns `true` if the path exists.
```lisp
(path:exists '/var/www/html/index.html')
; true
```

### (`path:chmod` \<path> \<mode>)
Changes the permissions of the given path. Value is assumed to be in octal.
```lisp
(path:chmod '/var/www/html/index.html' 777)
; true
```

### (`path:chdir` \<path>)
Changes the current directory.
```lisp
(path:chdir '/var/www/html')
; true
```

### (`path:rename` \<source> \<target>)
Renames a file or directory.
```lisp
(path:rename '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:symlink` \<link> \<target>)
Creates a symbolic link. Not all systems support this, be careful when using this function.
```lisp
(path:symlink '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:link` \<link> \<target>)
Creates a hard link. Not all systems support this, be careful when using this function.
```lisp
(path:link '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:tempnam` \<prefix>)
Generates and returns a temporal file path with the specified prefix.
```lisp
(path:tempnam 'prefix')
; /tmp/prefix_5f3e2e7b7b7e4
```

### (`path:temp`)
Returns the path to the system's temporal folder.
```lisp
(path:temp)
; /tmp
```

<br/><br/>

# Locale
Provides locale-related information and formatting functions.

### (`locale:include-millis` \<value>)
Sets the datetime format to include milliseconds.
```lisp
(locale:include-millis true)
; true
```

### (`locale:number` \<format> \<value>)<br/>(`locale:number` \<value>)
Formats a value as a number with the specified format or uses [Locale.numeric] if no format is specified.
```lisp
(locale:number 1234567.89)
; 1,234,567.89

(locale:number ",3'" 1234567.89)
; 1'234'567,890
```

### (`locale:integer` \<format> \<value>)<br/>(`locale:integer` \<value>)
Formats a value as an integer with the specified format or uses [Locale.numeric] if no format is specified.
```lisp
(locale:integer 1234567.89)
; 1,234,568

(locale:integer ",3'" 1234567.89)
; 1'234'568
```

### (`locale:time` \<format> \<value>)<br/>(`locale:time` \<value>)
Formats a value as a time with the specified format or uses [Locale.time] if no format is specified.
```lisp
(locale:time "2024-12-31 23:15:37")
; 11:15 PM

(locale:time "%H:%M:%S" "2024-12-31 23:15:37")
; 23:15:37
```

### (`locale:date` \<format> \<value>)<br/>(`locale:date` \<value>)
Formats a value as a date with the specified format or uses [Locale.date] if no format is specified.
```lisp
(locale:date "2024-12-31 23:15:37")
; 31/12/2024

(locale:date "%Y-%m-%d" "2024-12-31 23:15:37")
; 2024-12-31
```

### (`locale:datetime` \<format> \<value>)<br/>(`locale:datetime` \<value>)
Formats a value as a date and time with the specified format or uses [Locale.datetime] if no format is specified.
```lisp
(locale:datetime "2024-12-31 23:15:37")
; 31/12/2024 23:15

(locale:datetime "%Y-%m-%d %H:%M:%S" "2024-12-31 23:15:37")
; 2024-12-31 23:15:37
```

### (`locale:gmt` \<value>)
Formats a value as a GMT date and time.
```lisp
(locale:gmt "2024-12-31 23:15:37")
; Tue, 31 Dec 2024 23:15:37 GMT
```

### (`locale:utc` \<value>)
Formats a value as a UTC date and time.
```lisp
(locale:utc "2024-12-31 23:15:37")
; 2024-12-31T23:15:37Z
```

### (`locale:iso-date` \<value>)
Formats a value as an ISO date.
```lisp
(locale:iso-date "2024-12-31 23:15:37")
; 2024-12-31
```

### (`locale:iso-time` \<value>)
Formats a value as an ISO time.
```lisp
(locale:iso-time "2024-12-31 23:15:37")
; 23:15:37
```

### (`locale:iso-datetime` \<value>)
Formats a value as an ISO date and time.
```lisp
(locale:iso-datetime "2024-12-31 23:15:37")
; 2024-12-31 23:15:37
```

### (`locale:format` \<format-type> \<value> [format])
Formats a value using the specified format type, which can be: NUMBER, INTEGER, TIME, DATE, DATETIME, GMT, UTC, SDATE, and SDATETIME.
```lisp
(locale:format "NUMBER" 1234567.89)
; 1,234,567.89

(locale:format "TIME" "2024-12-31 23:15:37")
; 11:15 PM

(locale:format "INTEGER" ",3'" 1234567.89)
; 1'234'568
```

<br/><br/>

# Math


### (`math:rand`)
Returns a pseudo random number between 0 and 65535.
```lisp
(math:rand)
; 4578
```

### (`math:abs` \<value>)
Returns the absolute value of the given value.
```lisp
(math:abs -5)
; 5
```

### (`math:round` \<value>)
Returns the number rounded up to the nearest integer.
```lisp
(math:round 5.5)
; 6

(math:round 5.4)
; 5
```

### (`math:fixed` \<value> [decimals=2])
Returns the number rounded to the specified number of decimals.
```lisp
(math:fixed 5.5112)
; 5.51

(math:fixed 5.4782)
; 5.48
```

### (`math:ceil` \<value>)
Returns the ceiling value of a given number.
```lisp
(math:ceil 5.1)
; 6
```

### (`math:floor` \<value>)
Returns the floor value of a given number.
```lisp
(math:floor 5.8)
; 5
```

### (`math:clamp` \<value> \<min> \<max>)
Clamps the given value to the range defined by [a, b].
```lisp
(math:clamp 5 1 10)
; 5

(math:clamp 0 1 10)
; 1

(math:clamp 15 1 10)
; 10
```

### (`math:align` \<value> \<size>)
Aligns the given value to the nearest multiple of the given size.
```lisp
(math:align 5 3)
; 6
```

### (`math:to-hex` \<value> [size=2])
Converts a number to a hexadecimal string.
```lisp
(math:to-hex 255)
; ff
```

### (`math:to-bin` \<value> [size=8])
Converts a number to a binary string.
```lisp
(math:to-bin 129)
; 10000001
```

### (`math:to-oct` \<value> [size=3])
Converts a number to an octal string.
```lisp
(math:to-oct 15)
; 17
```

### (`math:from-hex` \<value>)
Returns a number from a hexadecimal string.
```lisp
(math:from-hex "ff")
; 255
```

### (`math:from-bin` \<value>)
Returns a number from a binary string.
```lisp
(math:from-bin "10000001")
; 129
```

### (`math:from-oct` \<value>)
Returns a number from an octal string.
```lisp
(math:from-oct "17")
; 15
```

<br/><br/>

# Regular Expressions
Provides an interface to manipulate and operate strings using regular expressions.

### (`re:matches` \<pattern> \<text>)
Tests the regular expression pattern on the given text, returns `true` if it matches, or `false` otherwise.
```lisp
(re:matches "/\d+/" "123")
; true
```

### (`re:match` \<pattern> \<text>)
Returns an array containing the information of the first string that matches the pattern.
```lisp
(re:match "/\d/" "123")
; {0: "1"}
```

### (`re:match-all` \<pattern> \<text> [\<capture-index>])
Uses the pattern and tries to match as many items as possible from the given text string. Returns an array with the matched items.
```lisp
(re:match-all "/\d/" "123")
; ["1", "2", "3"]
```

### (`re:split` \<pattern> \<text>)
Splits the given string using the pattern as the delimiter, returns an array containing the split elements.
```lisp
(re:split "/[,;]/" "1,2;3")
; ["1", "2", "3"]
```

### (`re:replace` \<pattern> \<replacement> \<text> [limit=-1])
Replaces all the strings that match the pattern by the given replacement.
```lisp
(re:replace "/\d/" "X" "a123b")
; "aXXXb"

(re:replace "/\d/" "X" "a123b" 1)
; "aX23b"
```

### (`re:extract` \<pattern> \<text>)
Returns only the parts of the text that match the pattern.
```lisp
(re:extract "/\d/" "a123b")
; "123"
```

### (`re:get` \<pattern> \<text> [\<capture-index>])
Matches one text string and returns it. Returns `null` if no match found.
```lisp
(re:get "/\d/" "123")
; "1"

(re:get `/\d(\d)\d/` "123" 1)
; "2"
```

<br/><br/>

# Text
Utility functions to manipulate text strings.

### (`str:sub` \<start> [count] \<value>)<br/>(`substr` \<start> [count] \<value>)
@deprecated Use `str:sub` or `str:slice` instead.
<br/>Returns a substring of a given string. Negative values in `start` are treated as offsets from the end of the string.
```lisp
(str:sub 1 2 "hello")
; "el"

(str:sub -4 2 "world")
; "or"

(str:sub -3 "hello")
; "llo"

(str:sub 2 -2 "Привет!")
; "иве"
```

### (`str:slice` \<start> [end] \<value>)
Returns a substring of a given string. Negative values in `start` or `end` are treated as offsets from the end of the string.
```lisp
(str:slice 1 2 "hello")
; "e"

(str:slice -4 3 "world")
; "or"

(str:slice 2 -1 "Привеt!")
; "ивеt"
```

### (`str:count` \<value|array> \<string>)
Returns the number of occurrences of the given value in the string.
```lisp
(str:count "l" "hello")
; 2

(str:count ["l" "h"] "hello")
; 3
```

### (`lpad` \<length> [pad] \<string>)
Pads a value by adding a character to the left until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(lpad 5 "0" "123")
; 00123

(lpad 5 "123")
; ..123
```

### (`rpad` \<length> [pad] \<string>)
Pads a value by adding a character to the right until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(rpad 5 "0" "123")
; 12300

(rpad 5 "123")
; 123..
```

### (`upper` \<value>)
Converts the value to upper case.
```lisp
(upper "hello")
; "HELLO"
```

### (`lower` \<value>)
Converts the value to lower case.
```lisp
(lower "HELLO")
; "hello"
```

### (`trim` [chars] \<value>)
Removes white space (or any of the given chars) and returns the result.
```lisp
(trim "  hello  ")
; "hello"
```

### (`ltrim` [chars] \<value>)
Removes white space (or any of the given chars) from the left and returns the result.
```lisp
(ltrim "  hello  ")
; "hello  "
```

### (`rtrim` [chars] \<value>)
Removes white space (or any of the given chars) from the right and returns the result.
```lisp
(rtrim "  hello  ")
; "  hello"
```

### (`starts-with?` \<value> \<text> [value-true=true] [value-false=false])
Returns boolean indicating if the given text starts with the given value.
```lisp
(starts-with? "hello" "hello world")
; true
```

### (`ends-with?` \<value> \<text> [value-true=true] [value-false=false])
Returns boolean indicating if the given text ends with the given value.
```lisp
(ends-with? "world" "hello world")
; true
```

### (`str:len` \<value>)
Returns the number of **characters** in the given text.
```lisp
(str:len "hello")
; 5
(str:len "你好")
; 2
(strlen "Привет!")
; 7
```

### (`str:replace` \<search> \<replacement> \<value>)
Replaces all occurences of `a` with `b` in the given value.
```lisp
(str:replace "hello" "world" "hello world")
; "world world"
```

### (`str:index` \<search> \<value>)
Returns the index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:index "world" "hello world")
; 6
```

### (`str:last-index` \<search> \<value>)
Returns the last index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:last-index "world" "hello world world")
; 12
```

### (`str:compare` \<a> \<b>)
Compares two strings and returns negative if a \< b, zero (0) if a == b, and positive if a > b.
```lisp
(str:compare "a" "b")
; -1

(str:compare "b" "a")
; 1

(str:compare "a" "a")
; 0
```

### (`str:tr` \<source-set> \<replacement-set> \<value>)
Translates characters in the given string.
```lisp
(str:tr "abc" "123" "cabc")
; 3123
```

### (`buf:len` \<value>)
Returns the length of the given binary buffer.
```lisp
(buf:len "hello")
; 5

(buf:len "Привет!")
; 13
```

### (`buf:sub` \<start> [count] \<value>)
Returns a substring of a binary string. Negative values in `start` are treated as offsets from the end of the string.
```lisp
(buf:sub 1 2 "hello")
; "el"

(buf:sub -4 2 "world")
; "or"
```

### (`buf:slice` \<start> [end] \<value>)
Returns a slice of a binary buffer. Negative values in `start` or `end` are treated as offsets from the end of the string.
```lisp
(buf:slice 1 2 "hello")
; "e"

(buf:slice -4 3 "world")
; "or"

(buf:slice 1 (+ 1 4) "universe")
; "nive"
```

### (`buf:compare` \<a> \<b>)
Compares two binary strings and returns negative if a \< b, zero (0) if a == b, and positive if a > b.
```lisp
(buf:compare "a" "b")
; -1

(buf:compare "b" "a")
; 1

(buf:compare "a" "a")
; 0
```

### (`buf:bytes` \<value>)
Returns the octet values of the characters in the given binary string.
```lisp
(buf:bytes "ABC")
; [65,66,67]

(buf:bytes "Любовь")
; [208,155,209,142,208,177,208,190,208,178,209,140]
```

### (`buf:from-bytes` \<octet-list>)
Returns the binary string corresponding to the given bytes.
```lisp
(buf:from-bytes (# 65 66 67))
; ABC

(buf:from-bytes (# 237 140 140 235 158 128 236 131 137))
; 파란색
```

### (`buf:uint8` \<int-value>)<br/>(`buf:uint8` \<string-value> [offset=0])
Returns the binary representation of the given 8-bit unsigned integer or reads an 8-bit unsigned integer from the binary string.
```lisp
(buf:uint8 0x40)
; "@"

(buf:uint8 "@")
; 0x40
```

### (`buf:uint16` \<int-value>)<br/>(`buf:uint16` \<string-value> [offset=0])
Returns the binary representation of the given 16-bit unsigned integer (little endian) or reads a 16-bit unsigned integer from the binary string.
```lisp
(buf:uint16 0x4041)
; "A@"

(buf:uint16 "A@")
; 0x4041
```

### (`buf:uint16be` \<int-value>)<br/>(`buf:uint16be` \<string-value> [offset=0])
Returns the binary representation of the given 16-bit unsigned integer (big endian) or reads a 16-bit unsigned integer from the binary string.
```lisp
(buf:uint16b 0x4041)
; "@A"

(buf:uint16be "@A")
; 0x4041
```

### (`buf:uint32` \<int-value>)<br/>(`buf:uint32` \<string-value> [offset=0])
Returns the binary representation of the given 32-bit unsigned integer (little endian) or reads a 32-bit unsigned integer from the binary string.
```lisp
(buf:uint32 0x40414243)
; "CBA@"

(buf:uint32 "CBA@")
; 0x40414243
```

### (`buf:uint32be` \<int-value>)<br/>(`buf:uint32be` \<string-value> [offset=0])
Returns the binary representation of the given 32-bit unsigned integer (big endian) or reads a 32-bit unsigned integer from the binary string.
```lisp
(buf:uint32be 0x40414243)
; "@ABC"

(buf:uint32be "@ABC")
; 0x40414243
```
