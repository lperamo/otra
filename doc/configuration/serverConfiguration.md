[Home](../../README.md) / [Installation](../mainConfiguration.md) / Setting Server configuration

### Setting server configuration

### Configuring DNS

As usual you'll find your hosts in `/etc/hosts` on *nix systems and under `C:\WINDOWS\system32\drivers\etc\hosts`.
Add those informations in it :

    127.0.0.1 yourdomain.com
    ::1       yourdomain.com
    
NB : `::1` is for IPv6.

#### Nginx version

There is no .htaccess for Nginx, see Apache section for that.

Now we will edit the configuration file `/etc/nginx/sites-available/yourdomain.com`.
You will need to use those lines or something similar according to your architecture:

    server {
        listen 80;
        listen [::]:80;
        set $rootPath /var/www/html;
        index Resources.php;
        
        server_name yourdomain.com;
        error_log /var/log/nginx/yourdomain/error.log error;
        access_log /var/log/nginx/yourdomain/access.log;
        
        root $rootPath/pathToYourSite/web;
        
        location / {
          try_files $uri /Resources.php$is_args$args;
        }
        
        location ~ \.php {
          include snippets/fastcgi-php.conf;
          fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
          
          fastcgi_param APP_ENV dev;
          fastcgi_param TEST_LOGIN yourLogin;
          fastcgi_param TEST_PASSWORD yourPassword;
        }
    }

The first location block indicates the entry point of the framework.
The second one handles the php parsing iva PHP-FPM.
It also specifies the kind of environment you want like development or production (`dev` or `prod`) like so ...

`fastcgi_param APP_ENV dev;`

and the database test ids ...

```
fastcgi_param TEST_LOGIN root;
fastcgi_param TEST_PASSWORD '';
```

For your own database, you can use the same system.
    
/!\ Of course, change those ids to something more secure !

You can put other things like cache and security.

Do not hesitate to look at https://github.com/nginx-boilerplate/nginx-boilerplate for further informations on how to
complete your configuration.

Once it is done, create a symbolic link to activate your site :

    sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
    
You can disable your site later with the `unlink` command.    
    
Create the logs folder if you did not do it yet :

    sudo mkdir /var/log/nginx/yourdomain
    
Verify that nothing already listens on port 80 and ,of course, (re)start PHP-FPM and Nginx :

    sudo service php7.3-fpm start && sudo service nginx start

#### Apache version

I recommend to not use `.htaccess` file when possible, it allows to deactivate the functionality that searches for this
kind of files.<br>

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