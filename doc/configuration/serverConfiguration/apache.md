[Home](../../../README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / Apache configuration

#### Apache configuration

I recommend to not use `.htaccess` file when possible, it allows to deactivate the functionality that searches for this
kind of files.<br>

In the future, you will be able to generate a server configuration file with this command :
              
`otra genServerConfig /etc/nginx/sites-available/dev.yourdomain.com dev apache`

...but it only works for Nginx as we speak. 
