<?php

if (file_exists(BASE_PATH . 'bundles/HelloWorld'))
{
  echo CLI_YELLOW, 'The bundle ', CLI_CYAN, 'HelloWorld', CLI_YELLOW, ' already exists.', END_COLOR, PHP_EOL;
  throw new \otra\OtraException('', 1, '', NULL, [], true);
}

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_ACTION_NAME = 5,
  ARG_INTERACTIVE = 6;
  $argv = [
    '0' => $argv[0],
    '1' => $argv[1],
    ARG_BUNDLE_NAME => 'HelloWorld',
    ARG_MODULE_NAME => 'frontend',
    ARG_CONTROLLER_NAME => 'index',
    ARG_ACTION_NAME => 'home',
    ARG_INTERACTIVE => 'false'
  ];

$consoleForce = true;
$bundleMask = 9;

// load console tools in order to ask questions (the following includes need those functions)

//define ('CORE_PATH', __DIR__ . '../../');
require CONSOLE_PATH . 'tools.php';

// creates bundles, modules, controllers, actions and their related folders
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';
require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
require CONSOLE_PATH . 'architecture/createController/checkControllerExistence.php';
require CONSOLE_PATH . 'architecture/createAction/createAction.php';

actionHandling(
  $interactive,
  $bundleName,
  $moduleName,
  $controllerName,
  $controllerPath,
  $argv[ARG_ACTION_NAME],
  $consoleForce
);

// We fixes the action file generated by filling the method (but here we just replaced the whole file)
$actionName = $argv[ARG_ACTION_NAME];
$upperActionName = ucfirst($argv[ARG_ACTION_NAME]);
$starterAction = BASE_PATH . 'bundles/' . $bundleName . '/' . $moduleName . '/controllers/'
  . $argv[ARG_CONTROLLER_NAME] . '/' . $upperActionName . 'Action.php';

file_put_contents(
  $starterAction,
  '<?php
/**
 * OTRA starter action
 */
namespace bundles\\' . $bundleName . '\\frontend\\controllers\\' . $controllerName . ';

use otra\Controller;

class ' . $upperActionName . 'Action extends Controller
{
  public function ' . $actionName . 'Action() {
    echo $this->renderView(\'' . $actionName . '.phtml\', []);
  }
}
?>
'
);

echo CLI_GREEN, "Action filled.", PHP_EOL;

// Adds a routes config file
$routeConfigurationFile = BASE_PATH . 'bundles/' . $bundleName . '/config/Routes.php';
file_put_contents(
  $routeConfigurationFile,
  "<?php
  return [
    'HelloWorld' => [
      'chunks' => ['/helloworld', 'HelloWorld', 'frontend', 'index', 'HomeAction'],
      'resources' => [
        'template' => true
      ]
    ]
  ];
?>"
);

echo 'Route configuration file ', CLI_LIGHT_CYAN,  $routeConfigurationFile, CLI_GREEN, ' created.',
  PHP_EOL;

// We create a starter layout
$starterLayout = BASE_PATH . 'bundles/' . $bundleName . '/views/' . 'layout.phtml';
file_put_contents(
  $starterLayout,
  file_get_contents(CONSOLE_PATH . 'architecture/helloWorldLayout.phtml')
);

echo CLI_GREEN, 'Starter layout created in ', CLI_LIGHT_CYAN, substr($starterLayout, strlen(BASE_PATH)),
  CLI_GREEN, '.', PHP_EOL;

// We create the action template that will use this layout
$starterTemplate = BASE_PATH . 'bundles/' . $bundleName . '/' . $moduleName . '/views/' .
  $controllerName . '/' . $actionName . '.phtml';

file_put_contents(
  $starterTemplate,
  "<?php
require BASE_PATH . 'bundles/" . $bundleName . "/views/layout.phtml';

block('title', 'Hello world starter template');
block('body');
?>
  <?php parent(); ?><br/>
  It's the basic template.
<?php endblock(); ?>
"
);

echo 'Starter template created in ', CLI_LIGHT_CYAN, substr($starterTemplate, strlen(BASE_PATH)),
  CLI_GREEN, '.', PHP_EOL;

echo 'Adding favicons...';
define('HELLO_WORLD_IMAGES_PATH', CORE_PATH . 'resources/img/HelloWorld/');
require CORE_PATH . 'tools/copyFilesAndFolders.php';

copyFileAndFolders(
  [
    HELLO_WORLD_IMAGES_PATH . 'favicon.ico',
    HELLO_WORLD_IMAGES_PATH . 'favicon.png'
  ],
  [
    BASE_PATH . 'web/favicon.ico',
    BASE_PATH . 'web/favicon.png'
  ]
);

// We update the routes configuration as we just add one route.
require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';

/* We update the class mapping since we have one action more.
 * Maybe we have launched this task with no class map and then the VERBOSE constant has already been defined during the
 * class map generation task.
*/
if (defined('VERBOSE') === false)
  define('VERBOSE', 0);
require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

echo CLI_GREEN, 'You can launch this example via the url ', CLI_LIGHT_CYAN, '/helloworld', CLI_GREEN,
'.', PHP_EOL, 'You can launch a PHP internal web server by typing ', CLI_LIGHT_CYAN, 'otra serve', CLI_GREEN, '.', END_COLOR, PHP_EOL;
?>
