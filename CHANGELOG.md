# v5.0.30 - Jul 17 2025

#### Wind
- Fixed bug causing banner message not to show.

#### Utils
- Fixed minor bug where `env:get` returned `false` instead of `null` when var didn't exist.

#### Expr
- Patched behavior of `include` to ensure definitions are added to the caller context.

#### Session
- Updated `session:name` and `session:id` to also work as setters.

<br/>

# v5.0.29 - Apr 09 2025

#### DateTime
- Function `datetime:millis` now accepts an input parameter.
- Functions `datetime:date` and `datetime:time` default to the current date and time respectively.
- Removed deprecated function `datetime:now-int`.

#### Configuration
- The environment variable ROSE_ENV is now also used to load configuration if present.

<br/>

# v5.0.28 - Apr 06 2025

#### Cookie
- Added new method `getAll` to return all cookies.
- Added functions `cookie:get-all` and `cookie:remove`
- Added support for default cookie_path and cookie_domain from the Session config section.
- Updated `cookie:set` and `cookie:remove` to allow to specify the cookie path.

<br/>

# v5.0.27 - Mar 22 2025

#### QoL
- Patched minor bug in Gateway causing relative path not to be detected in full.
- Improved error messages in Wind when using explicit endpoints.
- Default content-type is now "text/html".
- Fixed minor bugs with explicit endpoints.

<br/>

# v5.0.26 - Feb 28 2025

#### Wind
- Added support for custom endpoints using the `endpoints` configuration section, each field being of the form `method path = path:function`.

<br/>

# v5.0.25 - Feb 19 2025

#### QoL
- Added file_touch_disabled flag

<br/>

# v5.0.23 - Jan 26 2025

#### PostgreSQL
- Added support for 'bpchar'.

#### Utils
- Fixed bug in `env:get-all` returning wrong container type.

<br/>

# v5.0.22 - Dec 02 2024

#### Map
- Added 'map:diff' function to return differences between two maps.

<br/>

# v5.0.21 - Nov 28 2024

#### Database
- Added `tracing` parameter to the Driver class to debug final queries using `log_query` method.
- Removed deprecated `mssql` driver, use `sqlserver` instead.
- Added `prepare_param` and `prepare_query` to Driver class to prepare queries with values.
- Driver now supports placeholders: `?` `?()` `?[]` `?{}` and `?..` for prepared queries.
- PostgreSQL driver updated to return actual types from the database. Can be turned off by setting `postgres_types` to `false` in the [Database] section.

#### OpenSSL
- Added 'openssl:version' function.

<br/>

# v5.0.20 - Nov 20 2024

#### Text
- Added functions `ltrim` and `rtrim` finally!

#### Expr
- Updated `all`, `any`, `filter`, `find` and `find-index` to no longer need a code block.
- Updated `range` to allow just a single parameter.

<br/>

# v5.0.19 - Nov 18 2024

#### QoL
- Calling deprecated functions will issue a warning in the log file.
- Removed internal non-expr functions that were marked deprecated.

#### Text
- Moved all binary string manipulation functions to "buf:" prefix
- Added function `buf:cmp` and changed behavior of `str:compare` to be utf-8.
- Added function `buf:slice` to return a slice of a binary string.
- Added `str:sub` and `str:slice` functions.
- Deprecated `substr` function.
- Added `buf:slice` and renamed `buf:cmp` to `buf:compare` for consistency.

<br/>

# v5.0.18 - Nov 07 2024

#### QoL
- Fixed bug in DateTime when the timestamp is not provided as an integer.
- Updated error message of `assert` to be more verbose.
- Patched related code that uses `DateTime::getTimestamp` as integer to cast first.
- Fixed bug in `Path::extname` causing symbols in the extension not to be detected.

#### Text
- Added `str:count` to count the occurrences of a substring.

<br/>

# v5.0.17 - Sep 07 2024

#### DateTime
- Added support for milliseconds in datetime objects.
- Added option `include_millis` to DateTime section of system.conf to control default usage of milliseconds.
- Added option `%v` to show milliseconds (3 digits) in string formats.
- Added function `datetime:float` that returns UNIX time including milliseconds.
- Value is now optional in call to function `datetime:int`, when omited current datetime will be used.

#### Locale
- Added function `locale:include-millis` to control default usage of milliseconds in DateTime objects.

<br/>

# v5.0.16 - Aug 22 2024

#### QoL
- Fixed bug in Database\Connection when port is not specified.
- Added test/test-text.fn covering all text functions.

#### Text
- Refactored to ensure `unicode` is an optional parameter to most text functions.
- Updated functions to use unicode by default when called from Expr.

<br/>

# v5.0.15 - Aug 12 2024

#### Database
- Updated connection and drivers to allow port to be specified.

#### General
- Fixed minor bugs to increase compat with PHP 8.3.10

<br/>

# v5.0.14 - Aug 08 2024

#### DateTime
- Added correct month support to DateTime `add` and `sub` functions.
- Added function `datetime` as alias of `datetime:parse`.
- Added `datetime:tz` to get/set the current timezone.
- Function `datetime:time` now allows optional parameter "seconds" to get the seconds as well.

#### General
- Added "test-datetime" unit tests.

<br/>

# v5.0.13 - Jun 23 2024

#### General
- Added `sys:exit` to terminate the execution with an errorlevel / exit code.
- Patched `include` to no longer use import path.

<br/>

# v5.0.12 - Jun 23 2024

#### Cookies
- Added new attribute 'Partitioned' for cookies with SameSite=None

<br/>

# v5.0.11 - May 22 2024

#### Array
- Added functions `array:at`, `array:insert`, and `array:slices`.

<br/>

# v5.0.10 - May 22 2024

#### PostgreSQL
- Improved messages for foreign-key constraint errors.
- Added support for well formatted errors from "raise" statements.

#### File
- Function `file:copy` now supports streaming copy.

#### Wind
- Added support for a banner string (evaluated) in [Gateway.banner] to be shown as entry point when "f" is not provided.

#### Gateway
- Fixed bug in `gateway:continue` causing no output for the client.

<br/>

# v5.0.9 - May 19 2024

#### PostgreSQL
- Improved error messages for not-null constraints.
- Improved error messages for unique constraints.
- Prepared statements now support arrays of values.

#### General
- Database functions updated to accept arrays in prepared statements.

<br/>

# v5.0.8 - May 16 2024

#### General
- Updated PostgreSQL error message handler to provide more friendly messages.
- Fixed bug causing importing from "./" or "../" to not resolve to the correct folder.
- Updated deployment scripts.

<br/>

# v5.0.7 - May 14 2024

### General
- Updated documentation and added new deployment file.

<br/>

# v5.0.6 - May 13 2024

#### Wind
- Added service `wind-2` which returns the 'response' in the HTTP response status code and also in the response body.
- Added service `wind-3` which NO LONGER returns a 'response' field in the response body, only as the HTTP status code.

#### PostgreSQL
- Updated query processor to allow '?' as parameter placeholder, internally will be replaced by $1, $2, etc.

#### Expr
- Added support for mapped imports using the 'imports' configuration section.

#### Image
- Improved error message when position code is invalid for smartcut.

#### Regex
- Added parameter 'limit' to specify max number of occurrences to replace when using the `re:replace` function.

<br/>

# v5.0.5 - May 11 2024

#### Wind
- Updated error codes to match 1:1 the HTTP status codes.
- The `response` field is now mirrored into the HTTP response code.

#### Session
- Fixed bug causing a FalseError to be uncatched when using cookie-based sessions.

<br/>

# v5.0.4 - May 11 2024

#### File
- Function `file:size` now returns `null` if the file doesn't exist.

#### OpenSSL
- Fixed parameter requirements and parameter order for the `openssl:create` function.

#### Wind
- Updated WindError to expose 'data' field.

#### Expr
- Fixed behavior of 'throw' to respectfully throw an exception instead of a string.

<br/>

# v5.0.3 - May 04 2024

#### Utils
- Fixed bug in `env:set` function.

#### Expr
- Added escape sequence `\b` to specify ASCII 8.
- Added functions `range`, `sys:peak-memory`.

#### Request
- Added `request:error` which returns the last error string.
- Support for input, output and progress handlers completed.
- Added `request:input-file` and `request:output-file` for direct I/O.

#### File
- Added stream support with `stream:open`, `stream:close`, `stream:read` and `stream:write`.

#### Locale
- When number/integer format is not provided ".2," will be used by default.

#### Math
- Added `math:fixed` to round a number to a fixed number of decimals.

<br/>

# v5.0.2 - Apr 20 2024

#### Sys
- Added `sys:version` which returns the framework's version.

#### Expr
- Added functions `not-zero?`, and `not-in?`.
- Function `sys:sleep` now allows fractional (i.e. 0.5) values of seconds.

#### Database
- Added function `db:debug` to turn on/off tracing on demand.

#### Image
- Fixed bug in `image:save` causing wrong output format (base64).

#### Map
- Added function `map:del` to delete a key.
- Updated `map:length` to just `map:len`

#### Utils
- Added `strings:get` to replace old `s` function.

<br/>

# v5.0.1 - Apr 17 2024

#### JSON
- Fixed bug when using json:dump causing wrong formatting.

#### General
- Minor bug fixes

#### Expr
- Functions `inc` and `dec` now return the final value.
- Fixed minor bug with namespace operator.
- Updated `reply` function in Wind to log errors if content was already flushed and still echo it anyway as well.
- Added `shl` and `shr` functions for bit shifting.
- Fixed bug in parser causing comments to be parsed when inside a string.

#### Crypto
- Added `crypto:equals` a timing-attack safe string comparison.
- Added `crypto:random-bytes` and removed `utils:random-bytes`

#### OpenSSL
- Added functions to sign, verify and encrypt using public/private key.
- Improved documentation.
- Added functions `der:parse`, `der:get` and `der:extract`
- Added function `openssl:ciphers`

#### Database
- Normalized names of all functions.

#### Request
- Added functions `request:response-headers`, `request:headers`, and `request:status`

#### Session
- Added functions `session:load` and `session:save`

#### Math
- Added extra option to all transforming functions (i.e. `math:to-dec`) to specify number of alignment digits.
- Added `math:align` function.

#### Text
- Added support to read/write binary integers from/to a string.
- Added functions `str:uint8`, `str:uint16`, `str:uint16be`, `str:uint32` and `str:uint32be`

<br/>

# v5.0.0 - Apr 08 2024

- Refactored to comply with latest v5 syntax guide.
- Completed [documentation](./docs/README.md) of all available functions.
- Removed **all** deprecated functions.
- Refactored Gateway code and added new functions, see [Gateway](./docs/Gateway.md).
- Added [OpenSSL](./docs/OpenSSL.md) and [Cookies](./docs/Cookies.md) extensions.

<br/>

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

