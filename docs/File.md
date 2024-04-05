[&laquo; Go Back](./Expr.md)
# File


#### (`file:size` \<path>)
Returns the size of the file.
```lisp
(file:size "/var/www/image.jpg")
; 1024
```

#### (`file:dump` \<path>)
Dumps the file contents to the standard output.
```lisp
(file:dump "/var/www/image.jpg")
; Image data
```

#### (`file:mtime` \<path>)
Returns the modification time of the file as a datetime string (LTZ).
```lisp
(file:mtime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

#### (`file:atime` \<path>)
Returns the last access time of the file as a datetime string (LTZ).
```lisp
(file:atime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

#### (`file:touch` \<path> [datetime])
Sets the last modified time of a file (UTC). Datetime can be a datetime object, string or a unix timestamp.
```lisp
(file:touch "/var/www/image.jpg" "2024-03-24 03:36:18")
; true
```

#### (`file:read` \<path>)
Reads and returns the contents of the file.
```lisp
(file:read "/var/www/test.txt")
; "Hello, World!"
```

#### (`file:write` \<path> \<contents>)
Writes the contents to the file.
```lisp
(file:write "/var/www/test.txt" "Hello, World!")
; true
```

#### (`file:append` \<path> \<contents>)
Appends the given contents to the file.
```lisp
(file:append "/var/www/test.txt" "Hello, World!")
; true
```

#### (`file:remove` \<path>)
Deletes a file, returns `true` if success.
```lisp
(file:remove "/var/www/test.txt")
; true
```

#### (`file:unlink` \<path>)
Deletes a file. Does not check anything.
```lisp
(file:unlink "/var/www/test.txt")
; true
```

#### (`file:copy` \<source> \<target>)
Copies a file (overwrites if exists) to the given target, which can be directory or file.
```lisp
(file:copy "/var/www/image.jpg" "/var/www/images")
; true
```

#### (`file:create` \<path>)
Creates a file, returns `true` if the file was created, or `false` if an error occurred.
```lisp
(file:create "/var/www/test.txt")
; true
```
