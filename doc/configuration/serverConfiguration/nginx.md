[Home](../../../README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / Nginx

#### Nginx configuration

There is no .htaccess for Nginx, see Apache section for that.

Now we will edit the configuration file `/etc/nginx/sites-available/environment.yourdomain.com`.

You can now generate a server configuration file with this command :

`otra genServerConfig /etc/nginx/sites-available/dev.yourdomain.com dev`

You will need to use this configuration file or something similar according to your architecture...

In this file, the penultimate location block indicates the entry point of the framework.

The second one handles the php parsing via PHP-FPM.

It also specifies the kind of environment you want like development or production (`dev` or `prod`) like so ...

`fastcgi_param APP_ENV dev;`

...and the database test ids ...

```
fastcgi_param TEST_LOGIN root;
fastcgi_param TEST_PASSWORD '';
```

For your own database, you can use the same system.
    
/!\ Of course, change those ids to something more secure !

You can put other things like cache and security.

Do not hesitate to look at https://github.com/nginx-boilerplate/nginx-boilerplate for further information on how to
complete your configuration.

Once it is done, create a symbolic link to activate your site :

    sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
    
You can disable your site later with the `unlink` command.    
    
Create the logs' folder if you did not do it yet :

    sudo mkdir /var/log/nginx/yourdomain
    
Verify that nothing already listens on port 80 and ,of course, (re)start PHP-FPM and Nginx :

    sudo service php7.3-fpm start && sudo service nginx start
