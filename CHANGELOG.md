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

