<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

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
require CONSOLE_PATH . 'tools.php';

define('ARCHITECTURE_FOLDER', CONSOLE_PATH . 'architecture/');
define('ACTION_NAME', $argv[ARG_ACTION_NAME]);

if (!defined('OTRA_SUCCESS'))
  define('OTRA_SUCCESS', CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL);

// creates bundles, modules, controllers, actions and their related folders
/** @var bool $interactive */
require ARCHITECTURE_FOLDER . 'checkInteractiveMode.php';
/** @var string $bundleName */
require ARCHITECTURE_FOLDER . 'createBundle/checkBundleExistence.php';
/** @var string $moduleName */
require ARCHITECTURE_FOLDER . 'createModule/checkModuleExistence.php';
/** @var string $controllerName */
/** @var string $controllerPath */
require ARCHITECTURE_FOLDER . 'createController/checkControllerExistence.php';
require ARCHITECTURE_FOLDER . 'createAction/createAction.php';
require CORE_PATH . 'tools/copyFilesAndFolders.php';

actionHandling(
  $interactive,
  $bundleName,
  $moduleName,
  $controllerName,
  $controllerPath,
  ACTION_NAME,
  $consoleForce
);

define('BASE_PATH_LENGTH', strlen(BASE_PATH));

/**
 * @param string $source
 * @param string $destination
 * @param string $message
 *
 * @throws \otra\OtraException
 */
function copyAndShow(string $source, string $destination, string $message) : void
{
  copyFileAndFolders([HELLO_WORLD_STARTER_FOLDER . $source], [$destination]);
  echo $message, CLI_BLUE,  'BASE_PATH + ', CLI_LIGHT_CYAN, substr($destination, BASE_PATH_LENGTH), END_COLOR,
    ' created.', OTRA_SUCCESS;
}

// We fixes the action file generated by filling the method (but here we just replaced the whole file)
define('BUNDLE_FOLDER', BASE_PATH . 'bundles/' . $bundleName . '/');
define('MODULE_FOLDER', BUNDLE_FOLDER . $moduleName . '/');
$starterAction = MODULE_FOLDER . 'controllers/' . $argv[ARG_CONTROLLER_NAME] . '/' . ucfirst(ACTION_NAME) . 'Action.php';
define('HELLO_WORLD_STARTER_FOLDER', ARCHITECTURE_FOLDER . 'starters/helloWorld/');
copyFileAndFolders([HELLO_WORLD_STARTER_FOLDER . 'HomeAction.php'], [$starterAction]);
echo 'Action filled.', OTRA_SUCCESS;

// Adds a routes configuration file
copyAndShow(
  'Routes.php',
  BUNDLE_FOLDER . 'config/Routes.php',
  'Route configuration file '
);

// Adds a security configuration file
copyAndShow(
  'security',
  BUNDLE_FOLDER . 'config/security/',
  'Security configuration folder '
);

// We create a starter layout
copyAndShow(
  'layout.phtml',
  BUNDLE_FOLDER . 'views/' . 'layout.phtml',
  'Starter layout '
);

// We create the action template that will use this layout
copyAndShow(
  'home.phtml',
  MODULE_FOLDER . 'views/' . $controllerName . '/' . ACTION_NAME . '.phtml',
  'Starter template '
);

// We add the stylesheets
echo 'Adding stylesheets...', PHP_EOL;
const HELLO_WORLD_SCSS_FOLDER = MODULE_FOLDER . 'resources/scss/pages/HelloWorld/',
  HELLO_WORLD_STARTER_SCSS_FOLDER = HELLO_WORLD_STARTER_FOLDER . 'scss/',
  HELLO_WORLD_STARTER_SCSS_COMMON = '_common.scss',
  HELLO_WORLD_STARTER_SCSS_PRINT = 'print.scss',
  HELLO_WORLD_STARTER_SCSS_SCREEN = 'screen.scss';
copyFileAndFolders(
  [
    HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_COMMON,
    HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_PRINT,
    HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_SCREEN
  ],
  [
    HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_COMMON,
    HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_PRINT,
    HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_SCREEN
  ]
);

echo ERASE_SEQUENCE, 'Stylesheets added', OTRA_SUCCESS;

echo 'Adding favicons...', PHP_EOL;
define('HELLO_WORLD_IMAGES_PATH', CORE_PATH . 'resources/img/HelloWorld/');

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
echo ERASE_SEQUENCE, 'Favicons added', OTRA_SUCCESS;

// We update the routes configuration as we just add one route.
require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';

/* We update the class mapping since we have one action more.
 * Maybe we have launched this task with no class map and then the VERBOSE constant has already been defined during the
 * class map generation task.
*/
if (!defined('VERBOSE'))
  define('VERBOSE', 0);

require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

echo 'You can launch this example via the url ', CLI_LIGHT_CYAN, '/helloworld', END_COLOR,
  '.', PHP_EOL, 'You can launch a PHP internal web server by typing ', CLI_LIGHT_CYAN, 'otra serve', END_COLOR, '.',
  PHP_EOL;

