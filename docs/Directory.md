[&laquo; Go Back](./Expr.md)
# Directory


### (`dir:create` \<path>)
Creates a directory and all its parent directories (if needed). Returns boolean.
```lisp
(dir:create "/tmp/test")
; true
```

### (`dir:files` \<path> [regex-pattern])
Returns an array with file entries in the directory. Each entry is a map with keys `name` and `path`.
```lisp
(dir:files "/home")
; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
```

### (`dir:dirs` \<path> [regex-pattern])
Returns an array with directory entries in the directory. Each entry is a map with keys `name` and `path`.
```lisp
(dir:dirs "/home")
; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
```

### (`dir:entries` \<path> [regex-pattern])
Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
and directory entries each of which is a map with keys `name` and `path`.
```lisp
(dir:entries "/home")
; {
;    name: "home", path: "/home", 
;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
; }
```

### (`dir:files-recursive` \<path> [regex-pattern])
Returns an array with file entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
```lisp
(dir:files-recursive "/home")
; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
```

### (`dir:dirs-recursive` \<path> [regex-pattern])
Returns an array with directory entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
```lisp
(dir:dirs-recursive "/home")
; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
```

### (`dir:entries-recursive` \<path> [regex-pattern])
Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
and directory entries in the folder and all its subfolders. Each entry is a map with keys `name` and `path`.
```lisp
(dir:entries-recursive "/home")
; {
;    name: "home", path: "/home",
;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
; }
```

### (`dir:remove` \<path>)
Removes a directory (must be empty) returns `true` if success.
```lisp
(dir:remove "/tmp/test")
; true
```

### (`dir:remove-recursive` \<path>)
Removes a directory recursively and returns `true` if success.
```lisp
(dir:remove-recursive "/tmp/test")
; true
```

### (`dir:rmdir` \<path>)
Removes a directory (must be empty) without any checks. Returns `true` if success.
```lisp
(dir:rmdir "/tmp/test")
; true
```

### (`dir:copy` \<source> \<destination> [recursive=true] [overwrite=true])
Copies all files (and directories if `recursive` is set) from the `source` to the `destination` directories. If
`overwrite` is true the destination files will be overwritten.
```lisp
(dir:copy "/tmp/test" "/tmp/test2")
; true
```
