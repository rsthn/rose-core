[&laquo; Go Back](./README.md)
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

### (`env:set` \<name> \<value>)
Sets an environment variable.
```lisp
(env:set "HOME" "/home/user")
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

### (`sys:sleep` \<seconds>)
Sleeps for the given number of seconds.
```lisp
(sys:sleep 1)
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
