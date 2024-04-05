[&laquo; Go Back](./Expr.md)
# Server-Sent Events (SSE)


### (`sse:init`)
Initializes server-sent events (SSE) by setting several HTTP headers in the response and configuring the Gateway
to persist and disable any kind of output buffering.
```lisp
(sse:init)
; Content-Type: text/event-stream; charset=utf-8
; Transfer-Encoding: identity
; Content-Encoding: identity
; Cache-Control: no-store
; X-Accel-Buffering: no
```

### (`sse:send` [event-name] \<data>)
Sends the specified data to the browser as a server-sent event. If no event name is specified, the default event
name `message` will be used.
```lisp
(sse:send "message" "Hello, World!")
; event: message
; data: Hello, World!

(sse:send "info" (& list (# 1 2 3)))
; event: info
; data: {"list":[1,2,3]}
```

### (`sse:alive`)
Sends a comment line `:alive` to the browser to keep the connection alive if the last message sent was more than
30 seconds ago. Returns `false` if the connection was already closed by the browser.
```lisp
(while (sse:alive)
    ; Do something ...
    (sys:sleep 1)
)
```
