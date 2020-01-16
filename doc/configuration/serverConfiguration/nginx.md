[Home](/README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / Nginx

#### Nginx configuration

There is no .htaccess for Nginx, see Apache section for that.

Now we will edit the configuration file `/etc/nginx/sites-available/yourdomain.com`.
You will need to use those lines or something similar according to your architecture:
```
server {
    listen 80;
    listen [::]:80;
    set $rootPath /var/www/html;
    index indexDev.php;
    
    server_name yourdomain.com;
    error_log /var/log/nginx/yourdomain/error.log error;
    access_log /var/log/nginx/yourdomain/access.log;
    
    root $rootPath/pathToYourSite/web;
    
    location / {
      try_files $uri /indexDev.php$is_args$args;
    }
    
    location ~ \.php {
      include snippets/fastcgi-php.conf;
      fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
      
      fastcgi_param APP_ENV dev;
      fastcgi_param TEST_LOGIN yourLogin;
      fastcgi_param TEST_PASSWORD yourPassword;
    }
}
```

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
