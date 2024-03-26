[&laquo; Go Back](./Expr.md)
# HTTP Requests
Provides an interface to perform HTTP requests.

#### Executes a GET request and returns the response data.
```lisp
(request:get "http://example.com/api/currentTime")
; 2024-12-31T23:59:59
```

#### Executes a HEAD request and returns the HTTP status code. Response headers will be available using `request:headers`.
```lisp
(request:head "http://example.com/api/currentTime")
; 200
```

#### Executes a POST request and returns the response data.
```lisp
(request:post "http://example.com/api/login" (& "username" "admin" "password" "admin"))
; { "token": "eyJhbGciOiJIUzI" }
```

#### Executes a PUT request and returns the response data.
```lisp
(request:put "http://example.com/api/user/1" (& "name" "John Doe"))
; { "id": 1, "name": "John Doe" }
```

#### Executes a DELETE request and returns the response data.
```lisp
(request:delete "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

#### Executes a fetch request using the specified method and returns a parsed JSON response. Default method is `GET`.
```lisp
(request:fetch "http://example.com/api/currentTime")
; { "currentTime": "2024-12-31T23:59:59" }
```

#### Sets one or more headers for the next request.
```lisp
(request:header "Authorization: Bearer MyToken")
; true
```

#### Returns the response headers of the last request or a single header (if exists).
```lisp
(request:headers)
; { "content-type": "application/json", "content-length": "123" }

(request:headers "content-type")
; application/json
```

#### Enables or disables request debugging. When enabled request data will be output to the log file.
```lisp
(request:debug true)
; true
```

#### Enables or disables SSL verification for requests.
```lisp
(request:verify false)
; true
```

#### Sets the HTTP Authorization header for the next request.
```lisp
(request:auth "basic" "admin" "admin")
; true
```

#### Returns the HTTP status code of the last request.
```lisp
(request:code)
; 200
```

#### Returns the content-type of the last request. Shorthand for `(request:headers "content-type")` without the charset.
```lisp
(request:content-type)
; text/html
```

#### Returns the raw data returned by the last request.
```lisp
(request:data)
; HelloWorld
```

#### Clears the current headers, response headers and response data.
