[&laquo; Go Back](./Expr.md)
# Strings
Utility functions to manipulate text strings.

#### Returns a substring of a given string. Negative values in `start` indicate to start from the end of the string.
```lisp
(substr 1 2 "hello")
; "el"

(substr -4 2 "hello")
; "el"

(substr -3 "hello")
; "llo"
```

#### Pads a value by adding a character to the left until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(lpad 5 "0" "123")
; 00123

(lpad 5 "123")
; ..123
```

#### Pads a value by adding a character to the right until it reaches the desired length. If no padding character
is provided, it defaults to a space.
```lisp
(rpad 5 "0" "123")
; 12300

(rpad 5 "123")
; 123..
```

#### Converts the value to upper case.
```lisp
(upper "hello")
; "HELLO"
```

#### Converts the value to lower case.
```lisp
(lower "HELLO")
; "hello"
```

#### Converts the first letter in the word to upper case.
```lisp
(upper-first "hello")
; "Hello"
```

#### Removes white space (or any of the given chars) and returns the result.
```lisp
(trim "  hello  ")
; "hello"
```

#### Returns boolean indicating if the given text starts with the given value.
```lisp
(starts-with "hello" "hello world")
; true
```

#### Returns boolean indicating if the given text ends with the given value.
```lisp
(ends-with "world" "hello world")
; true
```

#### Returns the length of the given text in characters.
```lisp
(str:len "hello")
; 5
(str:len "你好")
; 2
```

#### Replaces a string (a) for another (b) in the given text.
```lisp
(str:replace "hello" "world" "hello world")
; "world world"
```

#### Returns the index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:index "world" "hello world")
; 6
```

#### Returns the last index of a sub-string in the given text. Returns -1 when not found.
```lisp
(str:last-index "world" "hello world world")
; 12
```

#### Compares two strings and returns negative if a \< b, zero (0) if a == b, and positive if a > b.
```lisp
(str:compare "a" "b")
; -1

(str:compare "b" "a")
; 1

(str:compare "a" "a")
; 0
```

#### Translates characters in the given string.
```lisp
(str:tr "abc" "123" "cabc")
; 3123
```
