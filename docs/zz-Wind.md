# Wind

## `call` \<name> [\<varName> \<varValue>...]
Calls the specified API function with the given parameters which will be available as globals to the target. Returns the response object.

The context of the target will be set to the current context, so any global variables will be available to the target function.

```lisp
(call "users.list" count 1 offset 10)
; Executes file `rcore/users/list.fn` in the current context.
```

## `icall` \<name> [\<varName> \<varValue>...]
Performs an **isolated call** to the specified API function with the given parameters which will be available as globals to the target. Returns the response object.

The context of the target will be set to a new context, so any global variables will **not be** available to the target function (except the pure `global` object).

```lisp
(icall "users.get" id 12)
; Executes file `rcore/users/get.fn` in a new context.
```

## `return` [\<data>]
Returns the specified data (or an empty object if none specified) to the current invoker (not the same as a function caller). The invoker is most of time the browser, except when using `call` or `icall`.

The response for the browser is always formatted for consistency:
- If `data` is an object and doesn't have the `response` field it will be added with the value `200` (OK).
- If `data` is an array, it will be placed in a field named `data` of the response object, with `response` code 200.

```lisp
(return (&))
; {"response":200}

(return (# 1 2 3))
; {"response":200,"data":[1,2,3]}
```

## `stop` [\<data>]
Stops execution of the current request and returns the specified data to the browser. If none specified, nothing will be returned.

Response is formatted following the same rules as `return`.

```lisp
(stop)
; (empty-string)
```

## `evt:init`

Initializes server-sent events by setting several HTTP headers in the response and configuring the Gateway to persist and disable any kind of output buffering.

The following headers are set:
- Content-Type: text/event-stream; charset=utf-8
- Transfer-Encoding: identity
- Content-Encoding: identity
- Cache-Control: no-store
- X-Accel-Buffering: no

```lisp
(evt:init)
; Headers will be set and Gateway ready for SSE.
```

## `evt:send` [\<event-name>] \<data>

Sends the specified data to the browser as a server-sent event. If no event name is specified, the default event name `message` will be used.

```lisp
(evt:send "Hello World")
; event: message
; data: Hello World

(evt:send "info" (& list (# 1 2 3)))
; event: info
; data: {"list":[1,2,3]}
```

## `evt:alive`

Sends a comment line `:alive` to the browser to keep the connection alive if the last message sent was more than 30 seconds ago. Returns `false` if the connection was already closed by the browser.

```lisp
(when (evt:alive)
    ; Do something ...
    (utils::sleep 1)
)
```
