[&laquo; Go Back](./Expr.md)
# File


#### Returns the size of the file.
```lisp
(file:size "/var/www/image.jpg")
; 1024
```

#### Dumps the file contents to the standard output.
```lisp
(file:dump "/var/www/image.jpg")
; Image data
```

#### Returns the modification time of the file as a datetime string (LTZ).
```lisp
(file:mtime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

#### Returns the last access time of the file as a datetime string (LTZ).
```lisp
(file:atime "/var/www/image.jpg")
; 2024-03-24 03:36:18
```

#### Sets the last modified time of a file (UTC). Datetime can be a datetime object, string or a unix timestamp.
```lisp
(file:touch "/var/www/image.jpg" "2024-03-24 03:36:18")
; true
```

#### Reads and returns the contents of the file.
```lisp
(file:read "/var/www/test.txt")
; "Hello, World!"
```

#### Writes the contents to the file.
```lisp
(file:write "/var/www/test.txt" "Hello, World!")
; true
```

#### Appends the given contents to the file.
```lisp
(file:append "/var/www/test.txt" "Hello, World!")
; true
```

#### Deletes a file, returns `true` if success.
```lisp
(file:remove "/var/www/test.txt")
; true
```

#### Deletes a file. Does not check anything.
```lisp
(file:unlink "/var/www/test.txt")
; true
```

#### Copies a file (overwrites if exists) to the given target, which can be directory or file.
```lisp
(file:copy "/var/www/image.jpg" "/var/www/images")
; true
```

#### Creates a file, returns `true` if the file was created, or `false` if an error occurred.
```lisp
(file:create "/var/www/test.txt")
; true
```
