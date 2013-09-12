@rem Make the php files executable as they are executable scripts
@echo PATHEXT environment variable update in order to make php files executable
@set path=%PATH%;"C:\wamp\bin\php\php5.3.10\;C:\wamp\bin\mysql\mysql5.5.20\bin\;"
@set pathext=%PATHEXT%;.PHP
@echo make an assocation between php files and the php application
@assoc .php=phpfile
@ftype phpfile="C:\wamp\bin\php\php5.3.10\php.exe -f "%1" -- %~2
@echo Updates the register...
@regedit /s phpAdditionKey.reg
@echo Register updated