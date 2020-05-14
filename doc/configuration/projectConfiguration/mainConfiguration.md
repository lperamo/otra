[Home](../../../README.md) / [Installation](../../configuration.md) / [Project configuration](../projectConfiguration.md) / Main configuration files

#### Main configuration files

Do not touch `bundles/config/Config.php`, it is a generated file.<br>
This file can be moved in a later version of the framework.<br>

The main configuration file is `config/AllConfig.php`.<br>
It contains generic configuration that will work for development and production environments.<br>

Values that can be modified in this file :
- `CACHE_PATH` : location of the generated files, especially the productions files for now
- `VERSION` : used for dynamic caching of CSS/JS resources
- `RESOURCE_FILE_MIN_SIZE` : aimed to load CSS/JS resources directly into the templates if their size is under this size.
- `FWK_HASH` : hash used to secure paths to the generated production files.

The related environment specific configuration files are in `config/dev/AllConfig.php` and `config/prod/AllConfig.php`
respectively.

Beware, in this file, the driver name must match with the related class name in OTRA (e.g. : `Pdomysql`). 

You can pass csp custom directives that will replace the default values of the "Master Controller" by setting something
like :
```php
public static array $csp = [
  'script-src' => "'self'"
];
```

You can have a file `bundles/App/config/Config.php` where `App` is the name of your bundle.
It will contains additional configuration that you want to pass like paths for example.

In the file `config/AdditionalClassFiles.php`, must contains the paths of OTRA classes that are included dynamically via
require(_once)/include(_once) directives. Most of the time, you do not have to touch this file.

Next section : [Routes configuration file](routesConfiguration.md)
