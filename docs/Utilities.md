[&laquo; Go Back](./Expr.md)
# Utilities


#### (`sys:sleep` \<seconds>)
Sleeps for the given number of seconds.
```lisp
(sys:sleep 1)
; true
```

#### (`sys:gc`)
Runs the garbage collector.
```lisp
(sys:gc)
; 1
```

#### (`sys:shell` \<command>)
Executes a shell command and returns the complete output as a string.
```lisp
(sys:shell "ls -l")
; "total 0\n-rw-r--r-- 1 user user 0 Jan  1 00:00 file.txt\n"
```

#### (`sys:exec` \<command>)
Executes a command and returns the exit code.
```lisp
(sys:exec "ls -l")
; 0
```

#### (`env:get-all`)
Returns all the environment variables.
```lisp
(env:get-all)
; {"HOME":"/home/user","PATH":"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"}
```

#### (`env:get` \<name>)
Returns an environment variable or `null` if not found.
```lisp
(env:get "HOME")
; "/home/user"
```

#### (`env:set` \<name> \<value>)
Sets an environment variable.
```lisp
(env:set "HOME" "/home/user")
; true
```

#### (`base64:encode` \<value>)
Encodes a value to base64.
```lisp
(base64:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ=="
```

#### (`base64:decode` \<value>)
Decodes a base64 value.
```lisp
(base64:decode "SGVsbG8sIFdvcmxkIQ==")
; "Hello, World!"
```

#### (`base64u:encode` \<value>)
Encodes a value to base64 URL-safe format, that is a base64 string with `+` as `-`, `/` as `_` and without any `=`.
```lisp
(base64u:encode "Hello, World!")
; "SGVsbG8sIFdvcmxkIQ"
```

#### (`base64u:decode` \<value>)
Decodes a base64 URL-safe value.
```lisp
(base64u:decode "SGVsbG8sIFdvcmxkIQ")
; "Hello, World!"
```

#### (`hex:encode` \<string>)
Encodes a string into hexadecimal format.
```lisp
(hex:encode "Hi!")
; "486921"
```

#### (`hex:decode` \<string>)
Decodes a hexadecimal string.
```lisp
(hex:decode "486921")
; "Hi!"
```

#### (`url:encode` \<value>)
Encodes a value to be used in a URL.
```lisp
(url:encode "Hello = World")
; "Hello%20%3D%20World"
```

#### (`url:decode` \<value>)
Decodes a URL-encoded value.
```lisp
(url:decode "Hello%20%3D%20World")
; "Hello = World"
```

#### (`url-query:str` \<fields>)
Converts a map to a URL query string.
```lisp
(url-query:str (& name "John" "age" 35))
; "name=John&age=35"
```

#### (`html-text:encode` \<value>)
Encodes a value to be used in HTML text.
```lisp
(html-text:encode "<Hello>")
; "&lt;Hello&gt;"
```

#### (`html-text:decode` \<value>)
Decodes a value encoded for HTML text.
```lisp
(html-text:decode "&lt;Hello&gt;")
; "<Hello>"
```

#### (`gz:compress` \<string>)
Compresses a string using the Gzip algorithm.
```lisp
(gz:compress "Hi!")
; (binary data)
```

#### (`gz:decompress` \<string>)
Decompresses a string compressed using the Gzip algorithm.
```lisp
(gz:decompress (gz:compress "Hi!"))
; "Hi!"
```

#### (`gz:deflate` \<string>)
Compresses a string using the Deflate algorithm.
```lisp
(gz:deflate "Hi!")
; (binary data)
```

#### (`gz:inflate` \<string>)
Inflates (decompresses) a string compressed using the Deflate algorithm.
```lisp
(gz:inflate (gz:deflate "Hi!"))
; "Hi!"
```

#### (`json:str` \<value>)
Converts a value to a JSON string.
```lisp
(json:str (& name "John" "age" 35))
; {"name":"John","age":35}
```

#### (`json:dump` \<value>)
Converts a value to a JSON string with indentation (pretty-print). Useful to debug nested structures.
```lisp
(json:dump (# 1 2 3))
; [1,2,3]
```

#### (`json:parse` \<string>)
Parses a JSON string and returns the value.
```lisp
(json:parse "[ 1, 2, 3 ]")
; [1,2,3]
```
