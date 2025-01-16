@echo off
rem Makes the php files executable as they are executable scripts ...
echo.
echo This file must be executed with administrator rights to work.

echo You have to put PHP in the PATH environment variable like 'set path=%PATH%;C:\your\path\to\the\php\folder\;'
echo Same step with Apache and MySQL should be a good thing.

echo Adding a shortcut to otra binary...
doskey otra=php bin/otra.php $*

echo PATHEXT environment variable update to make php files executable ...
set pathext=%PATHEXT%;.PHP

echo Makes an association between php files and the php application ...
assoc .php=phpfile > nul
ftype phpfile="C:\wamp\bin\php\php5.5.12\php.exe -f "%1" -- %~2" > nul
echo Updates the register...
regedit /s phpAdditionKey.reg
echo Register updated.
echo on
