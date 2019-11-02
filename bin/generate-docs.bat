@echo off

SET app=%0
SET lib=%~dp0

php "%lib%generate-docs.php" %*

echo.

exit /B %ERRORLEVEL%
