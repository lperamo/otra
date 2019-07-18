[Home](/README.md) / [Installation](../projectConfiguration.md) / [Project configuration](../projectConfiguration.md) / Routes configuration

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

  * the url
  * the app name
  * the bundle name
  * the controller name
  * the action name. Always specify this name in PascalCase.
  
* `resources` contains informations on client side.

  * `template` at true, it will tells that it is a static page.<br>
  It allows performance improvement by caching the generated page.
  
  * `bundle_css` contains all the css file names (without the extension) that are related to the bundle
  
  * `bundle_js` contains all the js file names (without the extension) that are related to the bundle

  * `_css` contains all the css file names (without the extension) that are related to the controller
  
  * `_js` contains all the js file names (without the extension) that are related to the controller
  
  * `core_css` css files from the framework's core
  
  * `core_js` js files from the framework's core
  
  * `bootstrap` TO COMPLETE
  
  * `post` default POST parameters
  
  * `get` default GET parameters
  
  * `session` default SESSION parameters
  
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