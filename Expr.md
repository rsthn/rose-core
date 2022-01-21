
# Built-In Functions

- [Core](#core)
- [Locale](#locale)
- [Database](#database)
- [DateTime](#datetime)
- [Image](#image)
- [Path](#path)
- [File](#file)
- [Directory](#directory)
- [HTTP](#http)
- [Session](#session)
- [Utils](#utils)
- [Array](#array)
- [Map](#map)
- [Regular Expressions](#regular-expressions)
- [Wind](#wind)

<br/>

# Extensions

- [Sentinel (Extension)](#sentinel)
- [Shield (Extension)](#shield)

<br/><br/><br/>

# Core

## `echo` \<value>+
Writes the specified arguments to standard output separated by space.
```lisp
(echo "Hello" "World")
; Hello World
```

## `#` \<value>+
Constructs an array/list.
```lisp
(# 1 2 3)
; [1,2,3]
```

## `&` \<name> \<expr> [\<name> \<expr>]*
Constructs an object.
```lisp
(& name "John" last "Doe")
; {"name":"John","last":"Doe"}
```

## `global`
Contains a truly global object, available to be used across all contexes.
```lisp
(set global.name "John")
(echo "My name is (global.name)")
; My name is John
```

## `len` \<value>
Returns the number of elements in the Array or Map. Or the length of the value if it is a primitive type.
```lisp
(len (# 1 2 3))
; 3
(len hello)
; 5
(len "nice")
; 4
(len 202121)
; 6
```

## `int` \<value>
Converts the given value to an integer.
```lisp
(int 4.25)
; 4
```

## `bool` \<value>
Converts the given value to pure boolean.
```lisp
(bool 0)
; false
```

## `str` \<value>+
Converts all specified values to string and concatenates them.
```lisp
(str "Today is " 18 "th")
; Today is 18th
```

## `float` \<value>
Converts the given value to pure numeric value.
```lisp
(float "4.25")
; 4.25
```

## `chr` \<value>
Returns the character corresponding to the given ASCII value.
```lisp
(chr 64)
; @
```

## `ord` \<value>
Returns the value corresponding to the given ASCII character.
```lisp
(ord "K")
; 75
```

## `not` \<value>
Returns the logical NOT of the value.
```lisp
(not 8)
; false
(not false)
; true
(not 0)
; true
```

## `neg` \<value>
Negates the value.
```lisp
(neg 12)
; -12
```

## `abs` \<value>
Returns the absolute value.
```lisp
(abs 3.14)
; 3.14
(abs -3.14)
; 3.14
```

## `and` \<value>+
Checks the truth-value of each value and returns `null` as soon as it finds the first falsey one, otherwise returns the last one.
```lisp
(and 1 2 3)
; 3
(and 1 0 12)
; null
(and true true true)
; true
(and false 12 2)
; null
```

## `or` \<value>+
Checks the truth-value of each value and returns the first truthy one found, or returns null if no truthy value found.
```lisp
(or 1 2 3)
; 1
(or 0 false "Hello" false)
; Hello
```

## `coalesce` \<value>+
Checks the null-value of each value and returns the first non-null found.
```lisp
(coalesce false 0 12)
; false
(coalesce 0 12)
; 0
(coalesce null null 5)
; 5
(coalesce null null)
; null
```

## `eq` \<value1> \<value2>
## `eq?` \<value1> \<value2>
Equals-operator. Checks if both values are the same using loose-equality, returns boolean.
```lisp
(eq 12 "12")
; true
(eq 0 false)
; true
(eq false null)
; true
(eq true 12)
; true
(eq 12 13)
; false
```

## `ne` \<value1> \<value2>
## `ne?` \<value1> \<value2>
Not-equals operator. Checks if both values are different using loose-equality, returns boolean.
```lisp
(ne 12 "12")
; false
(ne 0 false)
; false
(ne false null)
; false
(ne true 12)
; false
(ne 12 13)
; true
```

## `lt` \<value1> \<value2>
## `lt?` \<value1> \<value2>
Less-than operator. Returns `true` if value1 < value2.
```lisp
(lt 1 2)
; true
(lt 10 10)
; false
```

## `le` \<value1> \<value2>
## `le?` \<value1> \<value2>
Less-than-or-equal operator. Returns `true` if value1 <= value2.
```lisp
(le 1 2)
; true
(le 10 10)
; true
```

## `gt` \<value1> \<value2>
## `gt?` \<value1> \<value2>
Greater-than operator. Returns `true` if value1 > value2.
```lisp
(gt 1 2)
; false
(gt 10 10)
; false
```

## `ge` \<value1> \<value2>
## `ge?` \<value1> \<value2>
Greater-than-or-equal operator. Returns `true` if value1 >= value2.
```lisp
(ge 1 2)
; false
(ge 10 10)
; true
```

## `isnotnull` \<value>
## `notnull?` \<value>
Returns `true` if the value is not `null` (identical comparison).
```lisp
(isnotnull 12)
; true
(isnotnull 0)
; true
(isnotnull false)
; true
(isnotnull null)
; false
```

## `isnull` \<value>
## `null?` \<value>
Returns `true` if the value is `null` (identical comparison).
```lisp
(isnull 12)
; false
(isnull 0)
; false
(isnull false)
; false
(isnull null)
; true
```

## `iszero` \<value>
## `zero?` \<value>
Returns `true` if the value is zero (loose comparison).
```lisp
(iszero 0)
; true
(iszero false)
; true
(iszero null)
; true
(iszero 1)
; false
```

## `typeof` \<value>
Returns a string with the type-name of the value. Possible values are: null, string, bool, array, object, int, number, and function.
```lisp
(typeof 12)
; int
(typeof 3.14)
; number
(typeof today)
; string
(typeof null)
; null
(typeof (# 1 2 3))
; array
(typeof (& value "Yes"))
; object
```

## `*` \<value>+
## `mul` \<value>+
Multiplies the values and returns the result.
```lisp
(* 2 -2 3)
; -12
```

## `/` \<value>+
## `div` \<value>+
Returns the result of dividing each value by the next one.
```lisp
(/ 100 10 2)
; 5
```

## `+` \<value>+
## `sum` \<value>+
Returns the sum of all values.
```lisp
(+ 1 2 3)
; 6
```

## `-` \<value>+
## `sub` \<value>+
Returns the result of subtracting each value by the next one.
```lisp
(- 10 5 -2)
; 7
```

## `mod` \<value>+
Returns the remainder of dividing each value by the next one.
```lisp
(mod 131 31 5)
; 2
```

## `pow` \<value>+
Returns the result of raising each number to the next one.
```lisp
(pow 3 2 4)
; 6561
```

## `min` \<value>+
Returns the minimum value.
```lisp
(min 10 4 2 12)
; 2
```

## `max` \<value>+
Returns the maximum value.
```lisp
(max 10 4 2 12)
; 12
```

## `void` \<block>
Executes the block and returns `null`.
```lisp
(void (+ 1 2 3))
; null
```

## `nop` \<block>+
Prevents execution of any of the blocks and returns `null`.
```lisp
(nop (+ 1 2 3) (echo "Good day"))
; null
```

## `dump` \<value>
Dumps the value into readable string format.
```lisp
(dump 12)
; 12
(dump true)
; true
(dump (# 1 2 3))
; [1,2,3]
```

## `set` (\<var-name> \<value>)+
## `=` (\<var-name> \<value>)+
Sets one or more variables in the data context.
```lisp
(set a 12)
(echo (a))
; 12
```

## `inc` \<var-name>
Increments the value of a variable.
```lisp
(set a 0)
(inc a)
; 1
```

## `dec` \<var-name>
Decrements the value of a variable.
```lisp
(set a 0)
(dec a)
; -1
```

## `append` \<var-name> \<value>
Appends the value to a variable.
```lisp
(append a "Hello")
(append a "World")
(a)
; HelloWorld
```

## `unset` \<var-name>+
Removes one or more variables from the data context.
```lisp
(set a 12)
(a)
; 12
(unset a)
(a)
; Error: Expression function `a` not found.
```

## `trim` \<args>
Returns the value without white-space on the left or right. The value can be a string or an array.
```lisp
(trim " Hello " " World ")
; ["Hello","World"]
(trim " Nice ")
; Nice
```

## `upper` \<args>
Returns the value in uppercase. The value can be a string or an array.
```lisp
(upper "Hello" "World")
; ["HELLO","WORLD"]
(upper "Nice")
; NICE
```

## `lower` \<args>
Returns the value in lower. The value can be a string or an array.
```lisp
(lower "Hello" "World")
; ["hello","world"]
(lower "Nice")
; nice
```

## `substr` \<start> \<count> \<string>
## `substr` \<start> \<string>
Returns a sub-string of the given value.
```lisp
(substr 0 2 "Hello")
; He
(substr 0 -1 "Hello")
; Hell
(substr -2 "Hello")
; lo
(substr -4 1 "Hello")
; e
```

## `replace` \<search> \<replacement> \<args>
Replaces all occurences of `search` with `replacement` in the given value. The value can be a string, a sequence or a string.
```lisp
(replace "l" "w" "Hello")
; Hewwo
(replace "l" "w" "Hello" "World")
; ["Hewwo","Worwd"]
(replace "l" "w" (# "Hello" "World"))
; ["Hewwo","Worwd"]
```

## `nl2br` \<args>
Converts all new-line chars in the value to `<br/>`, the value can be a string, sequence or array.
```lisp
(nl2br "Hello\nWorld")
; Hello<br/>World
```

## `%` \<tag-name> \<args>
Returns a string with the value inside an XML tag named `tag-name`, the value can be a string, sequence or an array.
```lisp
(% b hello world)
; <b>hello</b><b>world</b>
```

## `%%` \<tag-name> [\<attr> \<value>]* [\<value>]
Returns a string with the value inside an XML tag named `tag-name`, each pair of attr-value will become attributes of the tag and the last value will be treated as the tag contents.
```lisp
(%% b class red "Hello World")
; <b class="red">Hello World</b>
```

## `join` \<glue> \<array-expr>
## `join` \<array-expr>
Joins the array into a string. If `glue` is provided, it will be used as separator.
```lisp
(join (# a b c))
; abc
(join _ (# a b c))
; a_b_c
```

## `split` \<delimiter> \<str-expr>
## `split` \<str-expr>
Splits the string by the specified delimiter (or empty string if none specified). Returns an array.
```lisp
(split "," "A,B,C")
; ["A","B","C"]
```

## `keys` \<object-expr>
Returns an array with the keys of the object.
```lisp
(keys (& name "John" last "Doe"))
; ["name","last"]
```

## `values` \<object-expr>
Returns an array with the values of the object.
```lisp
(values (& name "John" last "Doe"))
; ["John","Doe"]
```

## `_each`
```lisp
```

## `_foreach`
```lisp
```

## `_for`
```lisp
```

## `?` \<expr> \<valueA> [\<valueB>]
Returns `valueA` if the expression is `true` otherwise returns `valueB` or empty string if valueB was not specified. This is a short version of the `if` function.
```lisp
(? true "Yes" "No")
; Yes
(? false "Yes")
; (empty-string)
```

## `??` \<valueA> \<valueB>
Returns `valueA` if it is not `null` or empty string or zero, otherwise returns `valueB`.
```lisp
(?? 0 false)
; false
(?? 1 false)
; 1
(?? true null)
; true
```

## `_if`
```lisp
```

## `_when`
```lisp
```

## `_when-not`
```lisp
```

## `_switch`
```lisp
```

## `_case`
```lisp
```

## `_break`
```lisp
```

## `_continue`
```lisp
```

## `_repeat`
```lisp
```

## `_loop`
```lisp
```

## `_while`
```lisp
```

## `expr_debug` \<value>
Writes the raw expression data to standard output. Used to debug expressions.
```lisp
(expr_debug (set name "Red"))
; [{"type":"template","data":[[{"type":"identifier","data":"set"}],[{"type":"identifier","data":"name"}],[{"type":"string","data":"Red"}]]}]
```

## `contains` \<expr> \<name>+
Returns `true` if the specified object contains all the specified keys. If it fails the global variable `err` will contain an error message.
```lisp
(set a (& name "John"))

(if (not (contains (a) name last))
	(throw "Missing field: (err)"))

; Missing field: last
```

## `has` \<key> \<map-expr>
## `has` \<value> \<list-expr>
Returns `true` if the object has the specified key, or if the array/list has the specified value.
```lisp
(has name (& name "Red"))
; true
(has 3 (# A B C))
; false
(has 2 (# A B C))
; true
```

## `_map`
```lisp
```

## `_filter`
```lisp
```

## `_select`
```lisp
```

## `_pipe`
```lisp
```

## `expand`
```lisp
```

## `_try`
```lisp
```

## `throw`
```lisp
```

## `_assert`
```lisp
```

## `yield`
```lisp
```

## `exit`
```lisp
```

## `_with`
```lisp
```

## `ret`
```lisp
```

## `_fn`
```lisp
```

## `_def-fn`
```lisp
```

## `_def`
```lisp
```

## `_def-alias`
```lisp
```

## `ns`
```lisp
```

## `include`
```lisp
```

## `import`
```lisp
```

## `zipmap`
```lisp
```

## `map-get`
```lisp
```

## `mapify`
```lisp
```


<br/>

# Sentinel

## `sentinel::password`
## `sentinel::status`
## `sentinel::auth-required`
## `sentinel::privilege-required`
## `sentinel::has-privilege`
## `sentinel::level-required`
## `sentinel::has-level`
## `sentinel::get-level`
## `sentinel::valid`
## `sentinel::validate`
## `sentinel::login`
## `sentinel::authorize`
## `sentinel::login:manual`
## `sentinel::login:forced`
## `sentinel::logout`
## `sentinel::reload`

<br/>

# Shield

## `_shield::field`
## `shield::begin`
## `shield::end`
## `shield::validate`
## `shield::validateData`

<br/>

# Locale

## `locale::number`
## `locale::integer`
## `locale::time`
## `locale::date`
## `locale::datetime`
## `locale::gmt`
## `locale::utc`
## `locale::iso_date`
## `locale::iso_time`
## `locale::iso_datetime`
## `locale::format`

<br/>

# Database

## `escape`
## `db::escape`
## `db::escape:name`
## `db::scalar`
## `db::scalars`
## `db::row`
## `db::row:array`
## `db::table`
## `db::table:array`
## `db::reader`
## `db::exec`
## `db::update`
## `db::insert`
## `db::lastInsertId`
## `db::affectedRows`
## `db::fields:update`
## `db::fields:insert`

<br/>

# DateTime

## `datetime::now`
## `datetime::now:int`
## `datetime::parse`
## `datetime::int`
## `datetime::sub`
## `datetime::diff`
## `datetime::add`
## `datetime::date`
## `datetime::time`
## `datetime::format`

<br/>

# Image

## `image::load`
## `image::save`
## `image::output`
## `image::data`
## `image::width`
## `image::height`
## `image::resize`
## `image::scale`
## `image::fit`
## `image::cut`
## `image::crop`
## `image::smartcut`

<br/>

# Path

## `path::fsroot`
## `path::basename`
## `path::extname`
## `path::name`
## `path::normalize`
## `path::dirname`
## `path::resolve`
## `path::join`
## `path::is_file`
## `path::is_dir`
## `path::exists`
## `path::chmod`
## `path::rename`

<br/>

# File

## `file::size`
## `file::dump`
## `file::mtime`
## `file::atime`
## `file::touch`
## `file::read`
## `file::write`
## `file::append`
## `file::remove`
## `file::copy`
## `file::create`

<br/>

# Directory

## `dir::create`
## `dir::files`
## `dir::dirs`
## `dir::entries`
## `dir::files:recursive`
## `dir::dirs:recursive`
## `dir::entries:recursive`
## `dir::remove`

<br/>

# HTTP

## `http::get`
## `http::post`
## `http::fetch`
## `http::header`
## `http::method`
## `http::auth`
## `http::code`
## `http::content-type`
## `http::data`

<br/>

# Session

## `session`
## `session::open`
## `session::close`
## `session::destroy`
## `session::clear`
## `session::name`
## `session::id`
## `session::isopen`
## `session::data`

<br/>

# Utils

## `configuration`
## `config`
## `c`
## `strings`
## `s`
## `s::lang`
## `resources`

## `gateway`
## `gateway::redirect`
## `gateway::flush`
## `gateway::persistent`

## `utils::rand`
## `utils::randstr`
## `utils::randstr:base64`
## `utils::uuid`
## `utils::unique`
## `utils::sleep`
## `utils::base64::encode`
## `utils::base64::decode`
## `utils::hex::encode`
## `utils::hex::decode`
## `utils::url::encode`
## `utils::url::decode`
## `utils::serialize`
## `utils::deserialize`
## `utils::urlSearchParams`
## `utils::html::encode`
## `utils::html::decode`
## `utils::json::stringify`
## `utils::json::prettify`
## `utils::json::parse`
## `utils::html`
## `utils::shell`

## `utils::hashes`
## `utils::hash`
## `utils::hash:binary`
## `utils::hmac`
## `utils::hmac:binary`

<br/>

# Array

## `array::new`
## `array::sort:asc`
## `array::sort:desc`
## `array::sortl:asc`
## `array::sortl:desc`
## `array::push`
## `array::unshift`
## `array::pop`
## `array::shift`
## `array::first`
## `array::last`
## `array::remove`
## `array::indexof`
## `array::length`
## `array::append`
## `array::unique`
## `array::reverse`
## `array::clear`

<br/>

# Map

## `map::new`
## `map::sort:asc`
## `map::sort:desc`
## `map::sortk:asc`
## `map::sortk:desc`
## `map::keys`
## `map::values`
## `map::set`
## `map::get`
## `map::remove`
## `map::keyof`
## `map::length`
## `map::merge`
## `map::clear`

<br/>

# Regular Expressions

## `re::matches`
## `re::matchFirst`
## `re::matchAll`
## `re::split`
## `re::replace`
## `re::extract`

<br/>

# Wind

## `header`
## `content-type`

## `evt::init`
## `evt::send`

## `stop`
## `return`
## `_echo`
## `_trace`
## `_call`
## `_icall`
