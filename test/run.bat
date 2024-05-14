@echo off
cls
php test\test.php
if errorlevel 1 goto :error
exit /b 0
goto :eof

:error
exit /b 1
