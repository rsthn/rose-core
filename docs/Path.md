[&laquo; Go Back](./Expr.md)
# Path


#### Returns the fsroot, the path from which the script is executed.
```lisp
(path:fsroot)
; /var/www/html
```

#### Returns the current working directory.
```lisp
(path:cwd)
; /var/www/html
```

#### Returns the base name in the path (includes extension).
```lisp
(path:basename '/var/www/html/index.html')
; index.html
```

#### Returns the extension name.
```lisp
(path:extname '/var/www/html/index.html')
; .html
```

#### Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
```lisp
(path:name '/var/www/html/index.html')
; index
```

#### Normalizes the separator in the given path.
```lisp
(path:normalize '/var\\www/html\\index.html')
; /var/www/html/index.html
```

#### Returns the directory name.
```lisp
(path:dirname '/var/www/html/index.html')
; /var/www/html
```

#### Returns the fully resolved path.
```lisp
(path:resolve './index.html')
; /var/www/html/index.html
```

#### Appends the given items to the specified path.
```lisp
(path:append '/var/www/html' 'index.html')
; /var/www/html/index.html
```

#### Returns `true` if the path points to a file.
```lisp
(path:is-file '/var/www/html/index.html')
; true
```

#### Returns `true` if the path points to a directory.
```lisp
(path:is-dir '/var/www/html')
; true
```

#### Returns `true` if the path points to a symbolic link.
```lisp
(path:is-link '/var/www/html/index.html')
; false
```

#### Returns `true` if the path exists.
```lisp
(path:exists '/var/www/html/index.html')
; true
```

#### Changes the permissions of the given path. Value is assumed to be in octal.
```lisp
(path:chmod '/var/www/html/index.html' 777)
; true
```

#### Changes the current directory.
```lisp
(path:chdir '/var/www/html')
; true
```

#### Renames a file or directory.
```lisp
(path:rename '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

#### Creates a symbolic link. Not all systems support this, be careful when using this function.
```lisp
(path:symlink '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

#### Creates a hard link. Not all systems support this, be careful when using this function.
```lisp
(path:link '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

#### Generates and returns a temporal file path with the specified prefix.
```lisp
(path:tempnam 'prefix')
; /tmp/prefix_5f3e2e7b7b7e4
```

#### Returns the path to the system's temporal folder.
```lisp
(path:temp)
; /tmp
```
