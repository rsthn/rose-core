[&laquo; Go Back](./README.md)
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

### (`re:replace` \<pattern> \<replacement> \<text>)
Replaces all the strings that match the pattern by the given replacement.
```lisp
(re:replace "/\d/" "X" "a123b")
; "aXXXb"
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
