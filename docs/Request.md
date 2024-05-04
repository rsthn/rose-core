[&laquo; Go Back](./README.md)
# HTTP Requests


### (`request:get` \<url> [fields...])
Executes a GET request and returns the response data.
```lisp
(request:get "http://example.com/api/currentTime")
; 2024-12-31T23:59:59
```

### (`request:head` \<url> [fields...])
Executes a HEAD request and returns the HTTP status code. Response headers will be available using `request:response-headers`.
```lisp
(request:head "http://example.com/api/currentTime")
; 200
```

### (`request:post` \<url> [fields...])
Executes a POST request and returns the response data.
```lisp
(request:post "http://example.com/api/login" (& "username" "admin" "password" "admin"))
; { "token": "eyJhbGciOiJIUzI" }
```

### (`request:put` \<url> [fields...])
Executes a PUT request and returns the response data.
```lisp
(request:put "http://example.com/api/user/1" (& "name" "John Doe"))
; { "id": 1, "name": "John Doe" }
```

### (`request:delete` \<url> [fields...])
Executes a DELETE request and returns the response data.
```lisp
(request:delete "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

### (`request:patch` \<url> [fields...])
Executes a PATCH request and returns the response data.
```lisp
(request:patch "http://example.com/api/user/1")
; { "id": 1, "name": "John Doe" }
```

### (`request:fetch` [method] \<url> [fields...])
Executes a fetch request using the specified method and returns a parsed JSON response. Default method is `GET`.
```lisp
(request:fetch "http://example.com/api/currentTime")
; { "currentTime": "2024-12-31T23:59:59" }
```

### (`request:headers` [header-line|array])
Returns the current headers or sets one or more headers for the next request.
```lisp
(request:headers "Authorization: Bearer MyToken")
; true
(request:headers)
; ["Authorization: Bearer MyToken"]
```

### (`request:response-headers` [header])
Returns the response headers of the last request or a single header (if exists).
```lisp
(request:response-headers)
; { "content-type": "application/json", "content-length": "123" }

(request:response-headers "content-type")
; application/json
```

### (`request:debug` \<value>)
Enables or disables request debugging. When enabled request data will be output to the log file.
```lisp
(request:debug true)
; true
```

### (`request:verify` \<value>)
Enables or disables SSL verification for requests.
```lisp
(request:verify false)
; true
```

### (`request:auth` "basic" \<username> \<password>)<br/>(`request:auth` "basic" \<username>)<br/>(`request:auth` "bearer" \<token>)<br/>(`request:auth` \<token>)<br/>(`request:auth` false)
Sets the HTTP Authorization header for the next request.
```lisp
(request:auth "basic" "admin" "admin")
; true
```

### (`request:status`)
Returns the HTTP status code of the last request.
```lisp
(request:status)
; 200
```

### (`request:error`)
Returns the last error message.
```lisp
(request:error)
; Could not resolve host
```

### (`request:content-type`)
Returns the content-type of the last request. Shorthand for `(request:headers "content-type")` without the charset.
```lisp
(request:content-type)
; text/html
```

### (`request:data`)
Returns the raw data returned by the last request.
```lisp
(request:data)
; HelloWorld
```

### (`request:clear`)
Clears the current headers, response headers and response data.

### (`request:output-handler` \<func>)
Sets the output handler for the next request.
```lisp
(request:output-handler (fn data (echo (data))))
; true
```

### (`request:output-file` \<file-path>)
Sets the output file for the next request.
```lisp
(request:output-file "output.txt")
; true
```

### (`request:input-handler` \<func>)
Sets the input handler for the next request.
```lisp
(request:input-handler (fn max_bytes (ret "....")))
; true
```

### (`request:input-file` \<file-path>)
Sets the input file for the next request.
```lisp
(request:input-file "sample.jpg")
; true
```

### (`request:progress-handler` \<func>)
Sets the progress handler for the next request.
```lisp
(request:progress-handler (fn total_bytes curr_bytes ...))
; true
```
