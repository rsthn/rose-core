[&laquo; Go Back](./Expr.md)
# Cookies


#### Returns `true` if a cookie with the given name exists.
```lisp
(cookie:exists "MyCookie")
; false
```

#### Sets a cookie with the given name and value. Optionally, you can specify the time to live in seconds and the domain.
<br/>NOTE: By default the cookie will be set to never expire.
```lisp
(cookie:set "MyCookie" "hello" 3600)
; null
```

#### Returns the value of the cookie with the specified name.
```lisp
(cookie:get "MyCookie")
; "hello"
```
