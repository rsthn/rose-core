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

