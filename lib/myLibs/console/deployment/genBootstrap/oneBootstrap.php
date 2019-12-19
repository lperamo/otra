<?php
declare(strict_types=1);

use config\AllConfig;

$verbose = $argv[1];
$route = $argv[2];
define('BASE_PATH', substr(str_replace('\\', '/', __DIR__), 0, strlen(__DIR__) - strlen('lib/myLibs/console/deployment/genBootstrap'))); // Fixes windows awful __DIR__, BASE_PATH ends with /.
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');
require CORE_PATH . 'console/colors.php';

echo CLI_WHITE, str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL, END_COLOR;
$_SERVER['APP_ENV'] = 'prod';

require BASE_PATH . 'cache/php/ClassMap.php';

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
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

$params = \config\Routes::$_[$route];

// Init require section
require CORE_PATH . 'Session.php';
require CORE_PATH . 'bdd/Sql.php';
$defaultRoute = \config\Routes::$default['bundle'];

// in order to pass some conditions
$_SERVER['REMOTE_ADDR'] = 'console';
$_SERVER['REQUEST_SCHEME'] = 'HTTPS';
$_SERVER['HTTP_HOST'] = 'www.dev.save-our-space.com'; // TODO to put into a file to configure for each project ?

// Preparation of default parameters for the routes
if (true === isset($params['post']))
  $_POST = $params['post'];

if (true === isset($params['get']))
  $_GET = $params['get'];

// We put default parameters in order to not write too much times the session configuration in the routes file
$_SESSION['sid'] = ['uid' => 1, 'role' => 1];

if (true === isset($params['session']))
{
  foreach($params['session'] as $key => &$param)
  {
    $_SESSION[$key] = $param;
  }
}

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require CORE_PATH . 'console/deployment/genBootstrap/taskFileOperation.php';
$fileToInclude = BASE_PATH . str_replace(
    '\\',
    '/',
    \lib\myLibs\Router::get(
      $route,
      (true === isset($params['bootstrap'])) ? $params['bootstrap'] : [],
      false
    )
  ) . '.php';

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    'driver' => empty(\config\AllConfig::$dbConnections) === false
      && array_key_exists('driver', \config\AllConfig::$dbConnections[key(\config\AllConfig::$dbConnections)]) === true
      ? \config\AllConfig::$dbConnections[key(\config\AllConfig::$dbConnections)]['driver']
      : '',
    "_SERVER['APP_ENV']" => $_SERVER['APP_ENV']
  ]
);

set_error_handler(function ($errno, $message, $file, $line, $context) {
  throw new \lib\myLibs\OtraException($message, $errno, $file, $line, $context);
});

$chunks = $params['chunks'];

try
{
  contentToFile(
    fixFiles(
      $chunks[1],
      $route,
      file_get_contents($fileToInclude),
      $verbose,
      $fileToInclude
    ),
    $file_
  );
} catch(\Exception $e)
{
  echo (true === isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])
    ? '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}'
    : $e->getMessage();

  return;
}

if (hasSyntaxErrors($file_) === true)
  return;

compressPHPFile($file_, $file);
?>
