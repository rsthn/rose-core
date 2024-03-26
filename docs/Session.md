[&laquo; Go Back](./Expr.md)
# Session


#### Object with the current session data.
```lisp
(session)
; {"key1": "value1", "key2": "value2"}
```

#### Attempts to open an existing session or creates a new one (if `createSession` is `true`). The cookie name and other
configuration fields are obtained from the `Session` configuration section.
```lisp
(session:open)
; true
```

#### Closes the current session and writes the session data to permanent storage (file system or database). If the `activityOnly` 
parameter is `true` only the session's last activity field will be written to storage.

#### Destroys the current session, removes all session data including the session's cookie.

#### Clears the session data and keeps the same cookie name.

#### Returns the name of the session cookie, default ones comes from the `Session` configuration section.
```lisp
(session:name)
; "MySession"
```

#### Returns or sets the current session ID.
```lisp
(session:id)
; "oldSessionID"

(session:id "newSessionID")
; "newSessionID"
```

#### Returns boolean indicating if the session is open.
```lisp
(session:is-open)
; true
```
