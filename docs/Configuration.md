[&laquo; Go Back](./Expr.md)
# Configuration


#### (`config`)
Object containing the currently loaded system configuration fields.
```lisp
(config)
; {"General": {"version": "1.0.0"}}
```

#### (`config.env`)
Indicates what environment mode was used to load the configuration.
```lisp
(config.env)
; dev
```

#### (`config:parse` \<config-string>)
Parses the given configuration buffer and returns a map. The buffer data is composed of key-value pairs
separated by equal-sign (i.e. Name=John), and sections enclosed in square brakets (i.e. [General]).
<br/>
<br/>Note that you can use the equal-sign in the field value without any issues because the parser will look
<br/>only for the first to delimit the name.
<br/>
<br/>If a multi-line value is desired, single back-ticks (`) can be used after the equal sign to mark a start, and
<br/>on a single line to mark the end. Each line will be trimmed first before concatenating it to the value, and
<br/>new-line character is preserved.
```lisp
(config:parse "[General]\nversion=1.0.0")
; {"General": {"version": "1.0.0"}}
```

#### (`config:str` \<value>)
Converts the specified object to a configuration string. Omit the `value` parameter to use the
currently loaded configuration object.
```lisp
(config:str (& general (& version "1.0.0")))
; [General]
; version=1.0.0
```
