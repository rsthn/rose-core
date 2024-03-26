[&laquo; Go Back](./Expr.md)
# Utilities


#### Sleeps for the given number of seconds.
```lisp
(sys:sleep 1)
; true
```

#### Runs the garbage collector.
```lisp
(sys:gc)
; 1
```

#### Returns all the environment variables.
```lisp
(env:get-all)
; {"HOME":"/home/user","PATH":"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"}
```

#### Returns an environment variable or `null` if not found.
```lisp
(env:get "HOME")
; "/home/user"
```

#### Sets an environment variable.
```lisp
(env:set "HOME" "/home/user")
; true
```

#### Encodes a value to base64.
```lisp
(base64:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ=="
```

#### Decodes a base64 value.
```lisp
(base64:decode "SGVsbG8sIFdvcmxkIQ==")
; "Hello, World!"
```

#### Encodes a value to base64 URL-safe format, that is a base64 string with `+` as `-`, `/` as `_` and without any `=`.
```lisp
(base64u:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ"
```

#### Decodes a base64 URL-safe value.
```lisp
(base64u:decode "SGVsbG8sIFdvcmxkIQ")
; "Hello, World!"
```

#### Encodes a string into hexadecimal format.
```lisp
(hex:encode "Hi!")
; "486921"
```

#### Decodes a hexadecimal string.
```lisp
(hex:decode "486921")
; "Hi!"
```

#### Encodes a value to be used in a URL.
```lisp
(url:encode "Hello = World")
; "Hello%20%3D%20World"
```

#### Decodes a URL-encoded value.
```lisp
(url:decode "Hello%20%3D%20World")
; "Hello = World"
```

#### Converts a map to a URL query string.
```lisp
(url-query:stringify (& name "John" "age" 35))
; "name=John&age=35"
```

#### Encodes a value to be used in HTML text.
```lisp
(html-text:encode "<Hello>")
; "&lt;Hello&gt;"
```

#### Decodes a value encoded for HTML text.
```lisp
(html-text:decode "&lt;Hello&gt;")
; "<Hello>"
```

#### Compresses a string using the Gzip algorithm.
```lisp
(gz:compress "Hi!")
; (binary data)
```

#### Decompresses a string compressed using the Gzip algorithm.
```lisp
(gz:decompress (gz:compress "Hi!"))
; "Hi!"
```

#### Compresses a string using the Deflate algorithm.
```lisp
(gz:deflate "Hi!")
; (binary data)
```

#### Inflates (decompresses) a string compressed using the Deflate algorithm.
```lisp
(gz:inflate (gz:deflate "Hi!"))
; "Hi!"
```

#### Converts a value to a JSON string.
```lisp
(json:stringify (# 1 2 3))
; [1,2,3]
```

#### Converts a value to a JSON string with indentation (pretty). Useful with nested structures.
```lisp
(json:prettify (# 1 2 3))
; [1, 2, 3]
```

#### Parses a JSON string and returns the value.
```lisp
(json:parse "[ 1, 2, 3 ]")
; [1,2,3]
```
