@echo off
rem Makes the php files executable as they are executable scripts ...
echo.
echo PATHEXT environment variable update in order to make php files executable ...
set path=%PATH%;C:\wamp\bin\php\php5.5.12\;C:\wamp\bin\mysql\mysql5.6.17\bin\;
doskey console=php console.php $*
set pathext=%PATHEXT%;.PHP
echo makes an assocation between php files and the php application ...
assoc .php=phpfile > nul
ftype phpfile="C:\wamp\bin\php\php5.5.12\php.exe -f "%1" -- %~2" > nul
echo Updates the register...
regedit /s phpAdditionKey.reg
echo Register updated.
echo on
