[Home](../../README.md) / [Installation](../mainConfiguration.md) /

### Setting server configuration

#### Nginx version

Specify the kind of environment you want like development or production (`dev` or `prod`) like so :

`fastcgi_param APP_ENV dev;`

Specify your database test ids like so :

```
fastcgi_param TEST_LOGIN root;
fastcgi_param TEST_PASSWORD '';
```

For your own database, you can use the same system.<br>
    
/!\ Of course, change those ids to something more secure !

#### Apache version

TODO

#### Command line requirements

##### On *nix systems

You also have to set environment variables in your shell configuration to use the command line in good conditions.<br>
It may be `~/.bashrc`, `~/.zshrc` or another location depending on your environment.  

    export TEST_LOGIN root;
    export TEST_PASSWORD '';
    
##### On Windows

TODO

#### PHP requirements
You need PHP 7.3.x to use this framework.
It is planned that the framework will be compatible with PHP 7.4. 
You also need to activate some libraries as :

- mbstring 
    
#### Other requirements
You need Java in order to optimize javascript files as it uses Google Closure Compiler jar file.