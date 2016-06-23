<?
declare(strict_types=1);

use config\All_Config;

$verbose = $argv[1];
$route = $argv[2];

define('BASE_PATH', substr(str_replace('\\', '/', __DIR__), 0, 27)); // Fixes windows awful __DIR__, BASE_PATH ends with /. 27 is strlen(__DIR__) - strlen('lib/myLibs/console')
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');
require CORE_PATH . 'console/Colors.php';

echo white(), str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL, endColor();
define('XMODE', 'dev');

require BASE_PATH . 'cache/php/ClassMap.php';

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
$_SESSION['debuglp_'] = 'Dev';// We save the previous state of dev/prod mode
$firstFilesIncluded = get_included_files();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);

spl_autoload_register(function($className)
{
  if (isset(CLASSMAP[$className]))
  {
    require CLASSMAP[$className];
  } else {

    echo red(), 'CLASSMAP PROBLEM !!', PHP_EOL, debug_print_backtrace(), PHP_EOL;
    var_dump(CLASSMAP);
    echo PHP_EOL, endColor();

  }

});

require BASE_PATH . 'config/All_Config.php';

$params = \config\Routes::$_[$route];

// Init require section
require CORE_PATH . 'Session.php';
require CORE_PATH . 'bdd/Sql.php';
$defaultRoute = \config\Routes::$default['bundle'];
require BASE_PATH . 'bundles/' . $defaultRoute . '/Init.php';
call_user_func('bundles\\' . $defaultRoute . '\\Init::Init');

// in order to pass some conditions;
$_SERVER['REMOTE_ADDR'] = 'console';
$_SERVER['REQUEST_SCHEME'] = 'HTTPS';
$_SERVER['HTTP_HOST'] = 'www.dev.save-our-space.com'; // to put into a file to configure for each project ?

// Preparation of default parameters for the routes
$chunks = $params['chunks'];

if(true === isset($params['post']))
  $_POST = $params['post'];

if(true === isset($params['get']))
  $_GET = $params['get'];

// We put default parameters in order to not write too much times the session configuration in the routes file
$_SESSION['sid'] = ['uid' => 1, 'role' => 1];

if(true === isset($params['session']))
{
  foreach($params['session'] as $key => &$param)
  {
    $_SESSION[$key] = $param;
  }
}

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require CORE_PATH . 'console/TaskFileOperation.php';
$fileToInclude = BASE_PATH . str_replace('\\', '/', \lib\myLibs\Router::get(
    $route,
    (true === isset($params['bootstrap'])) ? $params['bootstrap'] : [],
    false
  )) . '.php';

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    'driver' => All_Config::$dbConnections[key(All_Config::$dbConnections)]['driver']
  ]);
//    'driver' => C:\LPAMP\www\framework-cms\config\dev\All_Config.php]);

set_error_handler(function ($errno, $message, $file, $line, $context) {
  throw new \lib\myLibs\Lionel_Exception($message, $errno, $file, $line, $context);
});

try
{
  contentToFile(fixFiles(file_get_contents($fileToInclude), $verbose, $fileToInclude), $file_);
} catch(\Exception $e)
{
  echo (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])
    ? '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}'
    : $e->getMessage();

  return;
}

if (hasSyntaxErrors($file_, $verbose))
  return;

compressPHPFile($file_, $file);

// Declaration of the special translation t function for templates...
function t(string $text) : string { return $text; }
?>
