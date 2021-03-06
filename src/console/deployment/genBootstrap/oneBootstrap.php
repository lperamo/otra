<?php
declare(strict_types=1);

use config\{AllConfig,Routes};
use otra\Router;

define('ONE_BOOTSTRAP_ARG_VERBOSE', 1);
define('ONE_BOOTSTRAP_ARG_ROUTE', 2);

define('OTRA_KEY_BOOTSTRAP', 'bootstrap');
define('OTRA_KEY_DRIVER', 'driver');

$verbose = (int) $argv[ONE_BOOTSTRAP_ARG_VERBOSE];
$route = $argv[ONE_BOOTSTRAP_ARG_ROUTE];
define('OTRA_PROJECT', strpos(__DIR__, 'vendor') !== false);
require __DIR__ . (OTRA_PROJECT
    ? '/../../../../../../..' // long path from vendor
    : '/../../../..'
  ) . '/config/constants.php';
require CONSOLE_PATH . 'colors.php';

echo CLI_WHITE, str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL, END_COLOR;
$_SERVER[APP_ENV] = 'prod';

require CLASS_MAP_PATH;

$_SESSION[OTRA_KEY_BOOTSTRAP] = 1; // in order to not really make BDD requests !
$firstFilesIncluded = get_included_files();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);

spl_autoload_register(function($className)
{
  if (isset(CLASSMAP[$className]))
  {
    require CLASSMAP[$className];
  } else {
    echo CLI_RED, 'CLASSMAP PROBLEM !!', PHP_EOL;
    debug_print_backtrace();
    echo PHP_EOL;
    var_dump(CLASSMAP);
    echo PHP_EOL, END_COLOR;
  }
});

require BASE_PATH . 'config/AllConfig.php';

$params = Routes::$_[$route];

// Init require section
require CORE_PATH . 'Session.php';
require CORE_PATH . 'bdd/Sql.php';
$defaultRoute = Routes::$default['bundle'];

// in order to pass some conditions
$_SERVER['REMOTE_ADDR'] = 'console';
$_SERVER['REQUEST_SCHEME'] = 'HTTPS';
$_SERVER['HTTP_HOST'] = AllConfig::$deployment['domainName'];

// Preparation of default parameters for the routes
if (isset($params['post']))
  $_POST = $params['post'];

if (isset($params['get']))
  $_GET = $params['get'];

// We put default parameters in order to not write too much times the session configuration in the routes file
// TODO remove this project related configuration line of code
$_SESSION['sid'] = ['uid' => 1, 'role' => 1];

if (isset($params['session']))
{
  foreach($params['session'] as $key => $param)
  {
    $_SESSION[$key] = $param;
  }
}

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';

// If it is an OTRA core route, we must change the path src from the config/Routes.php file by vendor/otra/otra/src
if (isset($params['core']) && $params['core'])
{
  $fileToInclude = substr(CORE_PATH,0, -5) . str_replace(
      ['\\', 'otra'],
      ['/', 'src'],
      Router::get(
        $route,
        (isset($params[OTRA_KEY_BOOTSTRAP])) ? $params[OTRA_KEY_BOOTSTRAP] : [],
        false
      )
    ) . '.php';
} else
{
  $fileToInclude = BASE_PATH . str_replace(
      '\\',
      '/',
      Router::get(
        $route,
        (isset($params[OTRA_KEY_BOOTSTRAP])) ? $params[OTRA_KEY_BOOTSTRAP] : [],
        false
      )
    ) . '.php';
}

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    OTRA_KEY_DRIVER => empty(AllConfig::$dbConnections) === false
      && array_key_exists(OTRA_KEY_DRIVER, AllConfig::$dbConnections[key(AllConfig::$dbConnections)]) === true
      ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER]
      : '',
    "_SERVER[APP_ENV]" => $_SERVER[APP_ENV],
    'temporaryEnv' => 'prod'
  ]
);

set_error_handler(function ($errno, $message, $file, $line, $context) {
  throw new \otra\OtraException($message, $errno, $file, $line, $context);
});

$chunks = $params['chunks'];

// TODO Add the retrieval of the classes via loaded via "throw new" in case they are not loaded via require, include or
//   an use statement. Other comment to remove once fixed, in fixFiles function of taskFileOperation.php
// For the moment, as a workaround, we will temporary explicitly add the OtraException file to solve issues.

try
{
  contentToFile(
    fixFiles(
      $chunks[1],
      $route,
      file_get_contents(CORE_PATH . 'OtraException.php') . '?>' . file_get_contents($fileToInclude),
      $verbose,
      $fileToInclude
    ),
    $file_
  );
} catch(Exception $e)
{
  echo (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
    ? '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}'
    : $e->getMessage();

  return;
}

if (hasSyntaxErrors($file_))
  return;

compressPHPFile($file_, $file);

