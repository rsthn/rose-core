[&laquo; Go Back](./Expr.md)
# Core


### (`echo` \<value...>)
Writes the specified values to standard output and adds a new-line at the end.
```lisp
(echo "Hello" " " "World")
(echo "!")
; Hello World
; !
```

### (`echo` \<value...>)
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

### (`float` \<value>)
Converts the given value to a float.
```lisp
(float "123.45")
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

### (`typeof` \<value>)<br/>(typeof 12)<br/>; int<br/><br/>(typeof 3.14)<br/>; number<br/><br/>(typeof today)<br/>; string<br/><br/>(typeof null)<br/>; null<br/><br/>(typeof (# 1 2 3))<br/>; array<br/><br/>(typeof (& value "Yes"))<br/>; object<br/><br/>(typeof (fn n 0))<br/>; function
Returns a string with the type-name of the value. Possible values are: `null`, `string`, `bool`, `array`,
`object`, `int`, `number`, and `function`.

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

### (`keys` \<object>)
Returns an array with the keys of the object.
```lisp
(keys (& "a" 1 "b" 2 "c" 3))
; ["a","b","c"]
```

### (`values` \<object>)
Returns an array with the values of the object.
```lisp
(values (& "a" 1 "b" 2 "c" 3))
; [1,2,3]
```

### (`for` [key-var:value-var | value-var] \<array> \<block>)
Evaluates the given block for each of the items in the array and returns the **original array**.
<br/>NOTE: Extra variables `i#` and `i##` (iterator variable with suffix `#` and `##`) are automatically introduced to
<br/>hold the index or key and numeric index of each item respectively (if no variable provided). Note that the later (##)
<br/>will always have a numeric value.
```lisp
(for x (# 1 2 3)
    (echo (* (x) 1.5))
)
; 1.5
; 3
; 4.5

(for key: val (& "a" 1 "b" 2)
    (echo "key: (key) value: (val)")
)
; key: a value: 1
; key: b value: 2
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

### (`break`)
Exits the current inner most loop.
```lisp
(for i (# 1 2 3 4 5 6 7 8 9 10)
    (echo (i))
    (break)
)
; 1
```

### (`continue`)
Skips execution and continues the next iteration of the current inner most loop.
```lisp
(for i (# 1 2 3 4 5 6 7 8 9 10)
    (when (odd? (i))
        (continue))
    (echo (i))
)
; 2 4 6 8 10
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
