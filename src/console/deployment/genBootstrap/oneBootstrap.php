<?php
declare(strict_types=1);

use config\{AllConfig,Routes};

$verbose = $argv[1];
$route = $argv[2];
define('OTRA_PROJECT', strpos(__DIR__, 'vendor') !== false);
require __DIR__ . (OTRA_PROJECT
    ? '/../../../../../../..' // long path from vendor
    : '/../../../..'
  ) . '/config/constants.php';
require CONSOLE_PATH . 'colors.php';

echo CLI_WHITE, str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL, END_COLOR;
$_SERVER['APP_ENV'] = 'prod';

require CLASS_MAP_PATH;

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

$params = Routes::$_[$route];

// Init require section
require CORE_PATH . 'Session.php';
require CORE_PATH . 'bdd/Sql.php';
$defaultRoute = Routes::$default['bundle'];

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

require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';

// If it is an OTRA core route, we must change the path src from the config/Routes.php file by vendor/otra/otra/src
if (isset($params['core']) && $params['core'])
{
  $fileToInclude = substr(CORE_PATH,0, -5) . str_replace(
      ['\\', 'otra'],
      ['/', 'src'],
      \otra\Router::get(
        $route,
        (true === isset($params['bootstrap'])) ? $params['bootstrap'] : [],
        false
      )
    ) . '.php';
} else {
  $fileToInclude = BASE_PATH . str_replace(
      '\\',
      '/',
      \otra\Router::get(
        $route,
        (true === isset($params['bootstrap'])) ? $params['bootstrap'] : [],
        false
      )
    ) . '.php';
}

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    'driver' => empty(AllConfig::$dbConnections) === false
      && array_key_exists('driver', AllConfig::$dbConnections[key(AllConfig::$dbConnections)]) === true
      ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)]['driver']
      : '',
    "_SERVER['APP_ENV']" => $_SERVER['APP_ENV'],
    'temporaryEnv' => 'prod'
  ]
);

set_error_handler(function ($errno, $message, $file, $line, $context) {
  throw new \otra\OtraException($message, $errno, $file, $line, $context);
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
