[&laquo; Go Back](./Expr.md)
# Strings
Utility functions to manipulate text strings.

#### (`substr` \<start> [count] \<value>)
Returns a substring of a given string. Negative values in `start` indicate to start from the end of the string.
```lisp
(substr 1 2 "hello")
; "el"

(substr -4 2 "world")
; "or"

(substr -3 "hello")
; "llo"

(substr 2 -2 "goodbye")
; "odb"
```

#### (`lpad` \<length> [pad] \<string>)
Pads a value by adding a character to the left until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(lpad 5 "0" "123")
; 00123

(lpad 5 "123")
; ..123
```

#### (`rpad` \<length> [pad] \<string>)
Pads a value by adding a character to the right until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(rpad 5 "0" "123")
; 12300

(rpad 5 "123")
; 123..
```

#### (`upper` \<value>)
Converts the value to upper case.
```lisp
(upper "hello")
; "HELLO"
```

#### (`lower` \<value>)
Converts the value to lower case.
```lisp
(lower "HELLO")
; "hello"
```

#### (`upper-first` \<value>)
Converts the first letter in the word to upper case.
```lisp
(upper-first "hello")
; "Hello"
```

#### (`trim` [chars] \<value>)
Removes white space (or any of the given chars) and returns the result.
```lisp
(trim "  hello  ")
; "hello"
```

#### (`starts-with` \<value> \<text>)
Returns boolean indicating if the given text starts with the given value.
```lisp
(starts-with "hello" "hello world")
; true
```

#### (`ends-with` \<value> \<text>)
Returns boolean indicating if the given text ends with the given value.
```lisp
(ends-with "world" "hello world")
; true
```

#### (`str:len` \<value>)
Returns the number of **characters** in the given text.
```lisp
(str:len "hello")
; 5
(str:len "你好")
; 2
(strlen "Привет!")
; 7
```

#### (`str:replace` \<search> \<replacement> \<value>)
Replaces all occurences of `a` with `b` in the given value.
```lisp
(str:replace "hello" "world" "hello world")
; "world world"
```

#### (`str:index` \<search> \<value>)
Returns the index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:index "world" "hello world")
; 6
```

#### (`str:last-index` \<search> \<value>)
Returns the last index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:last-index "world" "hello world world")
; 12
```

#### (`str:compare` \<a> \<b>)
Compares two strings and returns negative if a \< b, zero (0) if a == b, and positive if a > b.
```lisp
(str:compare "a" "b")
; -1

(str:compare "b" "a")
; 1

(str:compare "a" "a")
; 0
```

#### (`str:tr` \<source-set> \<replacement-set> \<value>)
Translates characters in the given string.
```lisp
(str:tr "abc" "123" "cabc")
; 3123
```

#### (`str:bytes` \<value>)
Returns the octet values of the characters in the given string.
```lisp
(str:bytes "ABC")
; [65,66,67]

(str:bytes "Любовь")
; [208,155,209,142,208,177,208,190,208,178,209,140]
```

#### (`str:from-bytes` \<octet-list>)
Returns the string corresponding to the given binary values.
```lisp
(str:from-bytes (# 65 66 67))
; ABC

(str:from-bytes (# 237 140 140 235 158 128 236 131 137))
; 파란색
```
