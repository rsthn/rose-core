[&laquo; Go Back](./Expr.md)
# DateTime


#### Returns the current date and time.
```lisp
(datetime:now)
; 2024-03-23 02:19:49

(datetime:now "America/New_York")
; 2024-03-23 04:20:05

(datetime:now "GMT-7.5")
; 2024-03-23 01:20:39
```

#### Returns the current date and time as a UNIX timestamp in UTC.
```lisp
(datetime:now-int)
; 1711182138
```

#### Returns the current datetime as a UNIX timestamp in milliseconds.
```lisp
(datetime:millis)
; 1711182672943
```

#### Parses a date and time string. Assumes source is in local timezone (LTZ) if no `sourceTimezone` specified. Note that the
default `targetTimezone` is the one configured in the `timezone` setting of the `Locale` configuration section.
```lisp
(datetime:parse "2024-03-23 02:19:49")
; 2024-03-23 02:19:49

(datetime:parse "2024-03-23 02:19:49" "America/New_York")
; 2024-03-23 04:19:49
```

#### Parses a date and time string and returns a UNIX timestamp.
```lisp
(datetime:int "2024-03-23 02:19:49")
; 1711181989
```

#### Returns the subtraction of two datetime in a given unit (`A` minus `B`). Defaults to seconds.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:sub "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; -3600
```

#### Returns the absolute difference between two datetime in a given unit (defaults to seconds).
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:diff "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; 3600
```

#### Returns the addition of a given delta value in a given unit (defaults to seconds) to a datetime.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:add "2024-03-23 02:19:49" 3600 "SECOND")
; 2024-03-23 03:19:49
```

#### Returns the date part of a datetime.
```lisp
(datetime:date "2024-03-23 02:19:49")
; 2024-03-23
```

#### Returns the time part of a datetime (only hours and minutes).
```lisp
(datetime:time "2024-03-23 02:19:49")
; 02:19
```

#### Formats a date and time string.
```lisp
(datetime:format "2024-03-23 02:19:49" "Year: %Y, Month: %m, Day: %d & Time: %H:%M:%S")
; Year: 2024, Month: 03, Day: 23 & Time: 02:19:49
```
