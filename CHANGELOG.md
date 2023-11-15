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

