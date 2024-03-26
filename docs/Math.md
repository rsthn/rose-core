[&laquo; Go Back](./Expr.md)
# Math


#### Returns a pseudo random number between 0 and 65535.
```lisp
(math:rand)
; 4578
```

#### Returns the absolute value of the given value.
```lisp
(math:abs -5)
; 5
```

#### Returns the number rounded up to the nearest integer.
```lisp
(math:round 5.5)
; 6

(math:round 5.4)
; 5
```

#### Returns the ceiling value of a given number.
```lisp
(math:ceil 5.1)
; 6
```

#### Returns the floor value of a given number.
```lisp
(math:floor 5.8)
; 5
```

#### Clamps the given value to the range defined by [a, b].
```lisp
(math:clamp 5 1 10)
; 5

(math:clamp 0 1 10)
; 1

(math:clamp 15 1 10)
; 10
```

#### Converts a number to a hexadecimal string.
```lisp
(math:to-hex 255)
; ff
```

#### Converts a number to a binary string.
```lisp
(math:to-bin 129)
; 10000001
```

#### Converts a number to an octal string.
```lisp
(math:to-oct 15)
; 17
```

#### Returns a number from a hexadecimal string.
```lisp
(math:from-hex "ff")
; 255
```

#### Returns a number from a binary string.
```lisp
(math:from-bin "10000001")
; 129
```

#### Returns a number from an octal string.
```lisp
(math:from-oct "17")
; 15
```
