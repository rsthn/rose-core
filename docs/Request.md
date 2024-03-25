[&laquo; Go Back](./Expr.md)
# HTTP Requests
Provides an interface to perform HTTP requests.

#### (`request:get` \<url> [fields...])
Executes a GET request and returns the response data.
```lisp
(request:get "http://example.com/api/currentTime")
; 2024-12-31T23:59:59
```

#### (`request:head` \<url> [fields...])
Executes a HEAD request and returns the HTTP status code. Response headers will be available using `request:headers`.
```lisp
(request:head "http://example.com/api/currentTime")
; 200
```

#### (`request:post` \<url> [fields...])
Executes a POST request and returns the response data.
```lisp
(request:post "http://example.com/api/login" (& "username" "admin" "password" "admin"))
; { "token": "eyJhbGciOiJIUzI" }
```

#### (`request:put` \<url> [fields...])
Executes a PUT request and returns the response data.
```lisp
(request:put "http://example.com/api/user/1" (& "name" "John Doe"))
; { "id": 1, "name": "John Doe" }
```

#### (`request:delete` \<url> [fields...])
Executes a DELETE request and returns the response data.
```lisp
(request:delete "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

#### (`request:fetch` [method] \<url> [fields...])
Executes a fetch request using the specified method and returns a parsed JSON response. Default method is `GET`.
```lisp
(request:fetch "http://example.com/api/currentTime")
; { "currentTime": "2024-12-31T23:59:59" }
```

#### (`request:header` \<header...>)
Sets one or more headers for the next request.
```lisp
(request:header "Authorization: Bearer MyToken")
; true
```

#### (`request:headers` [header])
Returns the response headers of the last request or a single header (if exists).
```lisp
(request:headers)
; { "content-type": "application/json", "content-length": "123" }

(request:headers "content-type")
; application/json
```

#### (`request:debug` \<value>)
Enables or disables request debugging. When enabled request data will be output to the log file.
```lisp
(request:debug true)
; true
```

#### (`request:verify` \<value>)
Enables or disables SSL verification for requests.
```lisp
(request:verify false)
; true
```

#### (`request:auth` "basic" \<username> \<password>)<br/>(`request:auth` "basic" \<username>)<br/>(`request:auth` "bearer" \<token>)<br/>(`request:auth` \<token>)<br/>(`request:auth` false)
Sets the HTTP Authorization header for the next request.
```lisp
(request:auth "basic" "admin" "admin")
; true
```

#### (`request:code`)
Returns the HTTP status code of the last request.
```lisp
(request:code)
; 200
```

#### (`request:content-type`)
Returns the content-type of the last request. Shorthand for `(request:headers "content-type")` without the charset.
```lisp
(request:content-type)
; text/html
```

#### (`request:data`)
Returns the raw data returned by the last request.
```lisp
(request:data)
; HelloWorld
```

#### (`request:clear`)
Clears the current headers, response headers and response data.
