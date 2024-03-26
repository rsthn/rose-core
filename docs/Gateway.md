[&laquo; Go Back](./Expr.md)
# Gateway
Provides an interface between clients and the system. No client can have access to the system without passing first through the Gateway.

#### Map with the request parameters from both GET and POST methods.
```lisp
(gateway.request)
; {"name": "John"}
```

#### Map with the server parameters sent via CGI.
```lisp
(gateway.server)
; {"SERVER_NAME": "localhost"}
```

#### Map with the HTTP headers sent via CGI.
```lisp
(gateway.headers)
; {"HOST": "localhost", "X_KEY": "12345"}
```

#### Map with the cookies sent by the client.
```lisp
(gateway.cookies)
; {"session": "123"}
```

#### Full URL address to the entry point of the active service. Never ends with slash.
```lisp
(gateway.ep)
; "http://localhost"
```

#### Server name obtained from the CGI fields or from the `server_name` field in the `Gateway` configuration section.
```lisp
(gateway.serverName)
; "localhost"
```

#### HTTP method used to access the gateway, will always be in uppercase.
```lisp
(gateway.method)
; "GET"
```

#### Remote address (and port) of the client.
```lisp
(gateway.remoteAddress)
; "127.0.0.1"

(gateway.remotePort)
; 12873
```

#### Relative URL root where the index file is found. Usually it is "/".
```lisp
(gateway.root)
; "/"
```

#### Local file system root where the index file is found.
```lisp
(gateway.fsroot)
; "/var/www/html"
```

#### Indicates if we're on a secure context (HTTPS).
```lisp
(gateway.secure)
; true
```

#### Object contaning information about the request body received.
```lisp
(gateway.input)
; {"contentType": "application/json", "size": 16, "path": "/tmp/1f29g87h12"}
```

#### Contains a parsed object if the content-type is `application/json`. For other content types, it will be `null` and the actual data can
be read from the file specified in the `path` field of the `input` object.
```lisp
(gateway.body)
; {"name": "John"}
```

#### Sets the HTTP status code to be sent to the client.
```lisp
(gateway:status 404)
; true
```

#### Sets a header in the current HTTP response.
```lisp
(gateway:header "Content-Type: application/json")
; true
```

#### Redirects the client to the specified URL by setting the `Location` header and exiting immediately.

#### Flushes all output buffers and prepares for immediate mode (unbuffered output).
```lisp
(gateway:flush)
; true
```

#### Configures the system to use persistent execution mode in which the script will continue to run indefinitely for as 
long as the server allows, even if the client connection is lost.
```lisp
(gateway:persistent)
; true
```

#### Sets the maximum execution time of the current operation. Use `NEVER` to disable the timeout.
```lisp
(gateway:timeout 30)
; true
```

#### Returns a response to the client and exits immediately.
```lisp
(gateway:return 200 "Hello, World!")
; Client will receive:
; Hello, World!
```
