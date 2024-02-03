# v4.1.8 - Feb 03 2024

#### Crypto
- Added "Crypto" native extension.
- Added functions `crypto:hashlist`, `crypto:hash`, `crypto:hash-bin`, `crypto:hmac`, `crypto:hmac-bin`.

#### Expr
- Added functions `base64u:encode`, `base64u:decode`, `url-query:stringify`, `html-text:encode`, `html-text:decode`.

#### General
- Marked several functions to be deprecated on next major version, alternatives will be provided when applicable.

<br/>

# v4.1.7 - Jan 22 2024

#### Expr
- Added support to specify key and value variable names in iterators instead of the default ones. Supported by: for, map, filter, every, some, find, findIndex, reduce, select, mapify, and groupify.
- Deprecated functions `each`, `%`, `%%`, and `void` have been removed.
- Added function `concat` to concatenate a sequence of values.

#### Net
- Added `http::status` function, performs a HEAD request and returns the HTTP status code.

<br/>

# v4.1.6 - Dec 27 2023

#### Connection
- Improved error reporting in all database functions.

#### MySQLi
- Added `set names utf8mb4` to connection startup code.

#### Expr
- Updated `eval` function to return the last value only.
- Function `has` now supports checking for sub-strings, such as (has 'aa' 'aab').

#### Utils
- Added `re::match-first` and marked `re::matchFirst` for deprecation.
- Added `re::match-all` and marked `re::matchAll` for deprecation.

#### Gateway
- Patched to ensure property 'method' is always in uppercase.

#### Session
- Patched bug causing null session object when using on-database session storage and cookie expired.

<br/>

# v4.1.5 - Nov 26 2023

#### DateTime
- Updated `datetime::diff` to always return positive value.
- Added `ISO` format option to return string as "YYYY-mm-ddTHH:ii:ss".

#### General
- Added JSON class to contain JSON-manipulation methods.
- Updated all classes that used `json_encode` and `json_decode` to use now JSON static methods.

#### PostgreSQL
- Added fixup to prevent `lastval` error when calling `getLastInsertId`.

#### Database
- Updated all methods accepting parameters for prepared statements to allow immediate values instead of just an array.

#### Utils
- Added function `utils::random-bytes` which returns binary data.

<br/>

# v4.1.4 - Nov 21 2023

#### Expr
- Added fixups to `eval` to ensure comments are removed and only returns the last value.
- Added support for `break` and `continue` to expressions `repeat`, `gather` and `loop`.
- Updated `repeat` to no longer construct an array, use `gather` instead.
- Removed support for infinite loop in `gather` expression.
- Default case for expression `case` is now just `else` instead of `default`, or you can ommit the keyword.
- Added support for full code blocks to `repeat`, and `gather`.

<br/>

# v4.1.2 - Nov 20 2023

#### General
- Updated documentation.

#### Expr
- Added support for 'break' and 'continue' to 'each' blocks.

#### Image
- Fixed some bugs related to warnings of precision loss when converting float to int.

#### Session
- Updated definition of database table.

<br/>

# v4.1.1 - Nov 15 2023

- 📝 Renamed the following expressions:
  | Old Name | New Name |
  | -------- | -------- |
  | trace::alt | trace-alt |
  | dir::files:recursive | dir::files-recursive |
  | dir::dirs:recursive | dir::dirs-recursive |
  | dir::entries:recursive | dir::entries-recursive |
  | dir::remove:recursive | dir::remove-recursive |

- 🔻 Removed the following deprecated expressions:
  | Expression | Direct Replacement | Alternative |
  | ---------- | ------------------ | ----------- |
  | array::indexof | array::indexOf |
  | map::keyof | map::keyOf |
  | escape | db::escape |
  | isnotempty | - | isnotnull
  | isempty | - | isnull
  | notnull? | not-null? |
  | notempty? | - | not-null?
  | not-empty? | - | not-null?
  | empty? | - | null?
  | foreach | - | for
  | expr_debug | - | -

<br/>

# v4.1.0 - Nov 15 2023

#### General
- Folder `rcore` has been deprecated, contents can be put in the root folder, and configuration files go now in the 'conf' folder.

#### Expr
- Improved error messages to be more consistent.
- Support added to `fn` to allow default parameters.
- Fixed bug causing function returning just a native keyword type to throw an error such as `(fn true)`
- Added support to allow a rest parameter (in `fn` and `def-fn`), should be prefixed with ampersand: `(fn a b &rest)`
- Variable `self` available in `fn` to access the function definer data scope.
- Fixed bug that allowed function names to start with underscore, that is now not supported to prevent issues with internal functions.
- Added `(debug::contextId)` returns the current execution context ID.
- Added `(get-fn function-name)` returns the reference to the specified function.
- Added `(set-fn function-name [function-reference|null])` sets or removes the reference of a function in the root context.

#### MySQLi
- Fixed bug causing strings such as '003' to be considered a number when it should be a string (prepared statements).
- Fixed bug causing prepared statement to return false when the execution was actually successful.

<br/>

# v4.0.75 - Nov 10 2023

#### Expr
- Added `std::compare` to compare strings in a C-like fashion.
- Added `array::sort` to compare arrays providing a custom comparison block.

<br/>

# v4.0.74 - Nov 09 2023

#### Net
- Added support for `head` method using the new `http::head` expression.

#### Configuration
- Active `env` is now available using (config.env) variable.

#### PostgreSQL
- Added support for prepared statements (using native $1, $2, etc).

#### Session
- Method `destroy` now opens the session (shallow) first to ensure it is deleted.

<br/>

# v4.0.73 - Oct 04 2023

#### SQLServer
- Using now `SQLSRV_CURSOR_CLIENT_BUFFERED` to speed up queries with client side buffering.

#### DateTime
- Added `datetime::millis` to get the current unix timestamp in milliseconds.

#### Expr
- Fixed bug when calling `has` or `contains` with a key starting with at (@).

<br/>

# v4.0.72 - Sep 20 2023

#### Utils
- Renamed `array::sort:asc`, `array::sort:desc`, `array::sortl:asc`, and `array::sortl:desc` to `array::sort-asc`, `array::sort-desc`, `array::sortl-asc`, and `array::sortl-desc` respectively.
- Renamed `map::sort:asc`, `map::sort:desc`, `map::sortk:asc`, and `map::sortk:desc` to `map::sort-asc`, `map::sort-desc`, `map::sortk-asc`, and `map::sortk-desc` respectively.

#### MySQLi
- Driver names `mysql` and `mysqli` both are now mapping to the same MySQLi driver.

<br/>

# v4.0.71 - Sep 18 2023

#### Driver
- Updated signature for `query` and `reader` to include new `params` parameter used with prepared statements.

#### General
- Updated all database drivers to comply with latest changes to Driver.
- Removed deprecated MySQL driver, using now only MySQLi. Driver names `mysql` and `mysqli` are now mapped to MySQLi.

#### Connection
- Updated comments to include more descriptive messages and example of expressions.
- Updated functions to allow now parameter `params` for prepared statements.

#### Utils
- Renamed `utils::randstr:base64` to `utils::randstr-base64`
- Improved `utils::xml::simplify` to remove redundant objects.
- Renamed `utils::hash:binary` and `utils::hmac:binary` to `utils::hash-binary` and `utils::hmac-binary`.

<br/>

# v4.0.70 - Sep 07 2023

#### SQLServer
- Fixed bug caused by dynamic property `num_fields`.
- Method `getLastError` will now attempt to load one if the current `last_error` is null.

<br/>

# v4.0.69 - Sep 07 2023

#### General
- Fixed minor bug causing warning when calling `utils::html`.

<br/>

# v4.0.68 - Aug 25 2023

#### General
- Started keeping track of CHANGELOG.md to record changes between versions.
- Corrected order of parameters for Regex::_replace to be pattern, replacement and text.
- Patched SQLServer to use Regex class instead of preg_replace.
- Patched Expr to use the correct order of parameters in calls to Regex::_replace.
- Patched Expr to use Text::replace instead of str_replace.
- Expression `utils::unique` now accepts a second optional parameter to specify the 64-byte charset for the code to generate.
- Expression `utils::html` now automatically sets content-type to 'text/html'.
- Added expression `reduce <iter-var> <init-var> <initial> <array> <expr>` to reduce an array to a single value.
- Updated global error handler to display the error in a friendly way when config.Gateway.display_errors is neither false or true.

<br/>

