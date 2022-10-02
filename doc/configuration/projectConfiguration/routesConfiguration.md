[Home](../../../README.md) / [Installation](../projectConfiguration.md) / [Project configuration](../projectConfiguration.md) / Routes configuration

Previous section : [Main configuration file](mainConfiguration.md)

#### Routes configuration

The file can be as simple as this one. 
```php
<?php
    return [
      'index' => [
        'chunks' => ['/', 'App', 'frontend', 'index', 'IndexAction'],
        'resources' => [
          'template' => true
        ]
      ]
    ];
?>
```
* `index` is the name of the route.

* `chunks` contains many parameters :

  * the url;
  * the app name;
  * the bundle name;
  * the controller name;
  * the action's name. Always specify this name in PascalCase.
  
* `resources` contains information on client side.

  * `template` at true, it will tell that it is a static page.<br>
  It allows performance improvement by caching the generated page.

  * **Assets - CSS and JS.**\
    Here `x` can be `css` or `js`. All these parameters are arrays containing `.x` file names.\
    No need to put their extension.

    * `app_x` for app related files. E.g.:\
      `'app_js' => ['test']` for `/myProjectPath/bundles/resources/js/test.js`
    
    * `bundle_x` for bundle related files. E.g.:\
    `'bundle_css' => ['test']` for `/myProjectPath/bundles/HelloWorld/resources/css/test.css`
  
    * `core_x` for OTRA/core related files. E.g.:\
      `'core_css' => ['test']` for `/myProjectPath/vendor/otra/otra/src/resources/css/test.css`
    
    * `module_x` for module related files. E.g.:\
      `'module_js' => ['my/test']` for `/myProjectPath/bundles/HelloWorld/frontend/resources/js/my/test.js`

    * `print_css` for print related files. Same path as module assets. E.g.:\
      `'print_css' => ['my/test']` for `/myProjectPath/bundles/HelloWorld/frontend/resources/js/my/test.css`
  
  * `bootstrap` must be an array that contains TO COMPLETE
  
  * `post` must be an array that contains default POST parameters
  
  * `get` must be an array that contains default GET parameters
  
  * `session` must be an array that contains default SESSION parameters
  
The route order is important so be sure to put the most generic ones at the end.

When we specify resources files, we can order them by telling their loading position like that :

```php
'resources' => [
  '_css' => ['users'],
  'bundle_css' => ['backendUsers'],
  'core_css' => ['lightbox'],
  '_js' => ['_5'=>'users'],
  'bundle_js' => ['base', 'backend', 'form', 'notifications'],
  'core_js' => ['_4' => 'lightbox']
]
```

`lightbox.js` will be loaded before `users.js`.

Next section : [Watching files](watchingFiles.md)
