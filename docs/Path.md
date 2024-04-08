[&laquo; Go Back](./README.md)
# Path


### (`path:fsroot`)
Returns the fsroot, the path from which the script is executed.
```lisp
(path:fsroot)
; /var/www/html
```

### (`path:cwd`)
Returns the current working directory.
```lisp
(path:cwd)
; /var/www/html
```

### (`path:basename` \<path>)
Returns the base name in the path (includes extension).
```lisp
(path:basename '/var/www/html/index.html')
; index.html
```

### (`path:extname` \<path>)
Returns the extension name.
```lisp
(path:extname '/var/www/html/index.html')
; .html
```

### (`path:name` \<path>)
Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
```lisp
(path:name '/var/www/html/index.html')
; index
```

### (`path:normalize` \<path>)
Normalizes the separator in the given path.
```lisp
(path:normalize '/var\\www/html\\index.html')
; /var/www/html/index.html
```

### (`path:dirname` \<path>)
Returns the directory name.
```lisp
(path:dirname '/var/www/html/index.html')
; /var/www/html
```

### (`path:resolve` \<path>)
Returns the fully resolved path.
```lisp
(path:resolve './index.html')
; /var/www/html/index.html
```

### (`path:append` \<path> \<items...>)
Appends the given items to the specified path.
```lisp
(path:append '/var/www/html' 'index.html')
; /var/www/html/index.html
```

### (`path:is-file` \<path>)
Returns `true` if the path points to a file.
```lisp
(path:is-file '/var/www/html/index.html')
; true
```

### (`path:is-dir` \<path>)
Returns `true` if the path points to a directory.
```lisp
(path:is-dir '/var/www/html')
; true
```

### (`path:is-link` \<path>)
Returns `true` if the path points to a symbolic link.
```lisp
(path:is-link '/var/www/html/index.html')
; false
```

### (`path:exists` \<path>)
Returns `true` if the path exists.
```lisp
(path:exists '/var/www/html/index.html')
; true
```

### (`path:chmod` \<path> \<mode>)
Changes the permissions of the given path. Value is assumed to be in octal.
```lisp
(path:chmod '/var/www/html/index.html' 777)
; true
```

### (`path:chdir` \<path>)
Changes the current directory.
```lisp
(path:chdir '/var/www/html')
; true
```

### (`path:rename` \<source> \<target>)
Renames a file or directory.
```lisp
(path:rename '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:symlink` \<link> \<target>)
Creates a symbolic link. Not all systems support this, be careful when using this function.
```lisp
(path:symlink '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:link` \<link> \<target>)
Creates a hard link. Not all systems support this, be careful when using this function.
```lisp
(path:link '/var/www/html/index.html' '/var/www/html/index2.html')
; true
```

### (`path:tempnam` \<prefix>)
Generates and returns a temporal file path with the specified prefix.
```lisp
(path:tempnam 'prefix')
; /tmp/prefix_5f3e2e7b7b7e4
```

### (`path:temp`)
Returns the path to the system's temporal folder.
```lisp
(path:temp)
; /tmp
```
