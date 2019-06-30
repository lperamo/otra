[Home](../../README.md) / [Installation](../mainConfiguration.md) /

### Setting client configuration

#### Main configuration file

You could use the file `tests/config/AllConfig.php` to know how you can configure it.<br>
Do not touch `bundles/config/Config.php`, it is a generated file.<br>
This file can be moved in a later version of the framework.<br>
The file you have to configure is `bundles/App/config/` where `App` is the name of ... your application.

#### Routes configuration file

The file can be as simple as this one. 

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