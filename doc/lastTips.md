[Home](../README.md) / A few last tips
                                
### A few last tips

To know all the commands simply type :

```bash
php console.php
```

As specified in this help, you can have more detailed help on a command :

```bash
php console.php help myCommand
```

To avoid that Composer adds autoloader files which are useless with OTRA, you should not pass by PHPStorm
(until we can add the option `--no-autoloader`) to update Composer packages.
