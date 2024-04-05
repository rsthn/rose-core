[&laquo; Go Back](./Expr.md)
# Locale
Provides locale-related information and formatting functions.

### (`locale:number` \<format> \<value>)<br/>(`locale:number` \<value>)
Formats a value as a number with the specified format or uses [Locale.numeric] if no format is specified.
```lisp
(locale:number 1234567.89)
; 1,234,567.89

(locale:number ",3'" 1234567.89)
; 1'234'567,890
```

### (`locale:integer` \<format> \<value>)<br/>(`locale:integer` \<value>)
Formats a value as an integer with the specified format or uses [Locale.numeric] if no format is specified.
```lisp
(locale:integer 1234567.89)
; 1,234,568

(locale:integer ",3'" 1234567.89)
; 1'234'568
```

### (`locale:time` \<format> \<value>)<br/>(`locale:time` \<value>)
Formats a value as a time with the specified format or uses [Locale.time] if no format is specified.
```lisp
(locale:time "2024-12-31 23:15:37")
; 11:15 PM

(locale:time "%H:%M:%S" "2024-12-31 23:15:37")
; 23:15:37
```

### (`locale:date` \<format> \<value>)<br/>(`locale:date` \<value>)
Formats a value as a date with the specified format or uses [Locale.date] if no format is specified.
```lisp
(locale:date "2024-12-31 23:15:37")
; 31/12/2024

(locale:date "%Y-%m-%d" "2024-12-31 23:15:37")
; 2024-12-31
```

### (`locale:datetime` \<format> \<value>)<br/>(`locale:datetime` \<value>)
Formats a value as a date and time with the specified format or uses [Locale.datetime] if no format is specified.
```lisp
(locale:datetime "2024-12-31 23:15:37")
; 31/12/2024 23:15

(locale:datetime "%Y-%m-%d %H:%M:%S" "2024-12-31 23:15:37")
; 2024-12-31 23:15:37
```

### (`locale:gmt` \<value>)
Formats a value as a GMT date and time.
```lisp
(locale:gmt "2024-12-31 23:15:37")
; Tue, 31 Dec 2024 23:15:37 GMT
```

### (`locale:utc` \<value>)
Formats a value as a UTC date and time.
```lisp
(locale:utc "2024-12-31 23:15:37")
; 2024-12-31T23:15:37Z
```

### (`locale:iso-date` \<value>)
Formats a value as an ISO date.
```lisp
(locale:iso-date "2024-12-31 23:15:37")
; 2024-12-31
```

### (`locale:iso-time` \<value>)
Formats a value as an ISO time.
```lisp
(locale:iso-time "2024-12-31 23:15:37")
; 23:15:37
```

### (`locale:iso-datetime` \<value>)
Formats a value as an ISO date and time.
```lisp
(locale:iso-datetime "2024-12-31 23:15:37")
; 2024-12-31 23:15:37
```

### (`locale:format` \<format-type> \<value> [format])
Formats a value using the specified format type, which can be: NUMBER, INTEGER, TIME, DATE, DATETIME, GMT, UTC, SDATE, and SDATETIME.
```lisp
(locale:format "NUMBER" 1234567.89)
; 1,234,567.89

(locale:format "TIME" "2024-12-31 23:15:37")
; 11:15 PM

(locale:format "INTEGER" ",3'" 1234567.89)
; 1'234'568
```
