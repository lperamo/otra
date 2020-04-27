[Home](../../../README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / Nginx

#### Nginx configuration

There is no .htaccess for Nginx, see Apache section for that.

Now we will edit the configuration file `/etc/nginx/sites-available/yourdomain.com`.
You will need to use those lines or something similar according to your architecture...
For a development environment : 
```
server
{
    listen         80;
    server_name    dev.otra-blog.tech;
    return         301 https://$server_name$request_uri; #Redirection 
}

server
{
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    set $rootPath /var/www/html/perso/otraSite;
    index indexDev.php;
    
    server_name dev.otra-blog.tech;
    error_log /var/log/nginx/dev.otra-blog.tech/error.log error;
    access_log /var/log/nginx/dev.otra-blog.tech/access.log;
    
    root $rootPath/web;

    ssl on;
    ssl_certificate /var/www/html/perso/otraSite/tmp/certs/server_crt.pem;
    ssl_certificate_key /var/www/html/perso/otraSite/tmp/certs/server_key.pem;

	# HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    
    # Clickjacking protection
    add_header X-Frame-Options "DENY";
    
    # XSS protections
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
      
    # Sending always referrer if it is secure
    add_header Referrer-Policy same-origin always;

    # CSP
    add_header Content-Security-Policy "frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'none'; connect-src 'self'; script-src 'strict-dynamic' 'sha256-ZA0L8/p6F3kaEsrqCWOJQrCFYglDBOGWd+U2fYVY+BY='; style-src 'self'; child-src 'self'; manifest-src 'self'";

	# Prevents hotlinking (others that steal our bandwith and assets)
	valid_referers none blocked ~.google. ~.bing. ~.yahoo. otra-blog.tech *.otra-blog.tech;
    
    if ($invalid_referer)
	{
        return 403;
    }

    # Forces static compression (for already gzipped files)
    gzip off;
    gzip_static always;

    # Blocking any page that don't send a referrer ...

    # Handling the gzipped web manifest
    location ~ /manifest\.gz
    {
        if ($http_referer = "")
        {
            return 403;
        }

        types
        {
            application/manifest+json gz;
        }

        gzip_types application/manifest+json;
        add_header Content-Encoding gzip;
    }

    # Handling CSS and JS
    location ~ /bundles/.*\.(css|js)$
    {
        if ($http_referer = "")
        {
            return 403;
        }

        root /var/www/html/perso/otraSite;
    }

    # Handling service worker, robots.txt and sitemaps
    location ~ /.*\.(js|txt|xml)$
    {
        if ($http_referer = "")
        {
            return 403;
        }
    }

    # Handling images
    location ~ /.*\.(ico|jpe?g|png|svg|webp)$
    {
        if ($http_referer = "")
        {
            return 403;
        }
    }

    location /
    {
        rewrite ^ /indexDev.php last;
    }
    
    location ~ \.php
    {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
      
        fastcgi_param APP_ENV dev;
        fastcgi_param TEST_LOGIN yourLogin;
        fastcgi_param TEST_PASSWORD yourPassword;
    }
}
```
For the production environment:
```
server
{
   listen         80;
   server_name    prod.otra-blog.tech;
   return         301 https://$server_name$request_uri; #Redirection
}

server
{
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    set $rootPath /var/www/html/perso/otraSite;
    index index.php;
    
    server_name prod.otra-blog.tech;
    error_log /var/log/nginx/prod.otra-blog.tech/error.log error;
    access_log /var/log/nginx/prod.otra-blog.tech/access.log;
    
    root $rootPath/web;

    ssl on;
    ssl_certificate /var/www/html/perso/otraSite/tmp/certs/server_crt.pem;
    ssl_certificate_key /var/www/html/perso/otraSite/tmp/certs/server_key.pem;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    
    # Clickjacking protection
    add_header X-Frame-Options "DENY";
    
    # XSS protections
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
      
    # Sending always referrer if it is secure
    add_header Referrer-Policy same-origin always;

    # CSP
    add_header Content-Security-Policy "frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'none'; connect-src 'self'; script-src 'strict-dynamic' 'sha256-1WuJV2/ENvT2CEpaRRnBQJ0K1GOLpyfxbLcfk6X0cO4='; style-src 'self'; child-src 'self'; manifest-src 'self'";

    # Prevents hotlinking (others that steal our bandwith and assets)
    valid_referers none blocked ~.google. ~.bing. ~.yahoo. otra-blog.tech *.otra-blog.tech;
    if ($invalid_referer)
    {
        #return 403;
    }

    # Forces static compression (for already gzipped files)
    gzip off;
    gzip_static always;

    # Blocking any page that don't send a referrer ...

    # Handling the gzipped web manifest
    location ~ /manifest\.gz
    {
        if ($http_referer = "")
        {
            return 403;
        }
        
        types
        {
            application/manifest+json gz;
        }
        
        gzip_types application/manifest+json;
        add_header Content-Encoding gzip;
    }

    # Handling CSS
    location ~ /cache/css/.*\.gz$
    {
        if ($http_referer = "")
        {
            return 403;
        }
        
        types
        {
            text/css gz;
        }
        
        gzip_types text/css;
        add_header Content-Encoding gzip;
        
        root /var/www/html/perso/otraSite;
    }

    # Handling JS
    location ~ /cache/js/.*\.gz$
    {
        if ($http_referer = "")
        {
            return 403;
        }

        types
        {
            application/javascript gz;
        }

        gzip_types application/javascript;
        add_header Content-Encoding gzip;
        
        root /var/www/html/perso/otraSite;
    }

    # Handling TPL
    location ~ /cache/tpl/.*\.gz$
    {
        if ($http_referer = "")
        {
            return 403;
        }
        
        default_type text/plain;
        
        types
        {
            text/html gz;
        }
        
        gzip_types text/html;
        add_header Content-Encoding gzip;
        
        root /var/www/html/perso/otraSite;
        try_files $uri /debug.php?otra_debug=tpl;
    }

    # Handling service worker, robots.txt and sitemaps
    location ~ /.*\.(js|txt|xml)$
    {
        if ($http_referer = "")
        {
            return 403;
        }
    }

    # Handling images
    location ~ /.*\.(ico|jpe?g|png|svg|webp)$
    {
        if ($http_referer = "")
        {
            return 403;
        }
    }

    # Handles rewriting
    location /
    {
        rewrite ^ /index.php last;
    }
    
    location ~ \.php
    {
      include snippets/fastcgi-php.conf;
      fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
      
      fastcgi_param APP_ENV prod;
      fastcgi_param TEST_LOGIN yourLogin;
      fastcgi_param TEST_PASSWORD yourPassword;
    }
}
```

The penultimate location block indicates the entry point of the framework.

The second one handles the php parsing via PHP-FPM.

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
