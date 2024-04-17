[&laquo; Go Back](./README.md)
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
