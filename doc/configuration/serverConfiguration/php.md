[Home](../../README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / PHP requirements

#### PHP requirements

You also need to activate some libraries as :

- mbstring 
- inotify

`Inotify` is a PECL extension that will allow OTRA to create file watchers.
To install `inotify`, you will need `PEAR` and some development dependencies like `phpsize` (`phpize`) :
```bash
sudo apt update
sudo apt install php-pear php7.3-dev
sudo pecl install inotify
echo "extension=inotify" > /etc/php/7.3/mods-available/inotify.ini
sudo phpenmod inotify
```
    
If php looks in wrong folders for inotify configurations, you may have not clean old php installation.

For example, for a PHP7.2 installation remove it that way :
    
```bash
apt purge php7.2 php7.2-common
```
