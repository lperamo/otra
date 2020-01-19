@echo off
rem Makes the php files executable as they are executable scripts ...
echo.
echo PATHEXT environment variable update in order to make php files executable ...

rem TODO verifies that all those directories exist, otherwise asks for their location
set path=%PATH%;C:\UniServerZ\core\php70\;C:\UniServerZ\core\mysql\bin\;C:\UniServerZ\core\apache2\bin\;C:\Program Files\MySQL\MySQL Server 5.6\bin;
set US_ROOTF=C:/UniServerZ
REM We take all the variables from the Uniserver config file
set CFGF=%US_ROOTF%\home\us_config\us_user.ini

FOR /F "TOKENS=1,2 delims=="  %%A IN (%CFGF%) DO (
  Set %%A=%%B
)
doskey console=php console.php $*
set pathext=%PATHEXT%;.PHP
echo makes an association between php files and the php application ...
assoc .php=phpfile > nul
ftype phpfile="C:\wamp\bin\php\php5.5.12\php.exe -f "%1" -- %~2" > nul
echo Updates the register...
regedit /s phpAdditionKey.reg
echo Register updated.
echo on
