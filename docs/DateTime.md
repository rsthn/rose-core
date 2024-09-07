[&laquo; Go Back](./README.md)
# DateTime
Provides functions to manipulate date and time.

### (`datetime:now` [targetTimezone])
Returns the current date and time.
```lisp
(datetime:now)
; 2024-03-23 02:19:49

(datetime:now "America/New_York")
; 2024-03-23 04:20:05

(datetime:now "GMT-7.5")
; 2024-03-23 01:20:39
```

### (`datetime:now-int`)
@deprecated Use `datetime:int` or `datetime:float` instead.
<br/>Returns the current date and time as a UNIX timestamp in UTC.
```lisp
(datetime:now-int)
; 1711182138
```

### (`datetime:millis`)
Returns the current datetime as a UNIX timestamp in milliseconds.
```lisp
(datetime:millis)
; 1711182672943
```

### (`datetime:parse` \<input> [targetTimezone] [sourceTimezone])
Parses a date and time string. Assumes source is in local timezone (LTZ) if no `sourceTimezone` specified. Note that the
default `targetTimezone` is the one configured in the `timezone` setting of the `Locale` configuration section.
```lisp
(datetime:parse "2024-03-23 02:19:49")
; 2024-03-23 02:19:49

(datetime "2024-03-23 02:19:49" "America/New_York")
; 2024-03-23 04:19:49
```

### (`datetime:int` [\<input>])
Parses a date and time string (or uses current time is none provided) and returns a UNIX timestamp.
```lisp
(datetime:int "2024-03-23 02:19:49")
; 1711181989
```

### (`datetime:float` [\<input>])
Parses a date and time string (or uses current time is none provided) and returns a UNIX timestamp.
```lisp
(datetime:float "2024-03-23 02:19:49.500")
; 1711181989.5
```

### (`datetime:sub` \<value-A> \<value-B> [unit])
Returns the subtraction of two datetime in a given unit (`A` minus `B`). Defaults to seconds.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:sub "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; -3600
```

### (`datetime:diff` \<value-A> \<value-B> [unit])
Returns the absolute difference between two datetime in a given unit (defaults to seconds).
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:diff "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
; 3600
```

### (`datetime:add` \<input> \<delta> [unit])
Returns the addition of a given delta value in a given unit (defaults to seconds) to a datetime.
<br/>Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
```lisp
(datetime:add "2024-03-23 02:19:49" 3600 "SECOND")
; 2024-03-23 03:19:49
```

### (`datetime:date` \<input>)
Returns the date part of a datetime.
```lisp
(datetime:date "2024-03-23 02:19:49")
; 2024-03-23
```

### (`datetime:time` \<input> [seconds=false])
Returns the time part of a datetime (only hours and minutes).
```lisp
(datetime:time "2024-03-23 02:19:49")
; 02:19
```

### (`datetime:format` \<input> \<format>)
Formats a date and time string.
```lisp
(datetime:format "2024-03-23 02:19:49" "Year: %Y, Month: %m, Day: %d & Time: %H:%M:%S")
; Year: 2024, Month: 03, Day: 23 & Time: 02:19:49
```

### (`datetime:tz` [timezone])
Returns or sets the global timezone.
```lisp
(datetime:tz)
; America/New_York
```
