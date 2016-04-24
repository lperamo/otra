<?
$verbose = $argv[1];
$route = $argv[2];

define('BASE_PATH', substr(str_replace('\\', '/', __DIR__), 0, -15)); // Fixes windows awful __DIR__, BASE_PATH ends with /
require BASE_PATH . 'lib/myLibs/core/console/Colors.php';

echo white(), $route, endColor();

define('XMODE', 'dev');

require BASE_PATH . 'config/Routes.php';
require BASE_PATH . 'cache/php/ClassMap.php';
require BASE_PATH . 'lib/myLibs/core/Router.php';

$params = \config\Routes::$_[$route];

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
$_SESSION['debuglp_'] = 'Dev';// We save the previous state of dev/prod mode
$firstFilesIncluded = get_included_files();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);

spl_autoload_register(function($className) use($classMap)
{
  /*if (!isset($classMap[$className]))
  {
    debug_print_backtrace();
    echo '********* ', $className, ' *************';die;
  }*/
  require $classMap[$className];
});

require BASE_PATH . 'config/All_Config.php';

// Init require section
require BASE_PATH . 'lib/myLibs/core/Session.php';
require BASE_PATH . 'lib/myLibs/core/bdd/Sql.php';
$defaultRoute = \config\Routes::$default['bundle'];
require BASE_PATH . 'bundles/' . $defaultRoute . '/Init.php';
call_user_func('bundles\\' . $defaultRoute . '\\Init::Init');
//var_dump(get_included_files());die;

$_SERVER['REMOTE_ADDR'] = 'console'; // in order to pass some conditions;

// Preparation of default parameters for the routes
$chunks = $params['chunks'];

if(isset($params['post']))
  $_POST = $params['post'];

if(isset($params['get']))
  $_GET = $params['get'];

// We put default parameters in order to not write too much times the session configuration in the routes file
$_SESSION['sid'] = ['uid' => 1, 'role' => 1];

if(isset($params['session']))
{
  foreach($params['session'] as $key => $param)
  {
    $_SESSION[$key] = $param;
  }
}

// We execute the route...
ob_start();

try
{
  \lib\myLibs\core\Router::get(
    $route,
    (isset($params['bootstrap'])) ? $params['bootstrap'] : []
  );
  $output = ob_get_clean();

  if(false !== ($pos = strpos($output, 'Notice')))
  {
    preg_match('@Notice.*\d@', $output, $matches);
    echo lightCyan(), ' Beware ... there are notices around !' . PHP_EOL;
    foreach($matches as $match) { echo $match; }
    unset($matches, $match);
  }

  unset($output);

  echo lightGreen(), str_pad('[EXECUTION]', 35 - strlen($route), ' ', STR_PAD_LEFT), endColor();
} catch(Exception $e)
{
  ob_end_clean();
  echo red(), str_pad('Fail.', 35 - strlen($route), ' ', STR_PAD_LEFT), PHP_EOL,
    str_pad('- ', 5, ' ', STR_PAD_LEFT) . $e->getMessage(), endColor(), PHP_EOL;

  return;
}

// ...and retrieve the PHP classes used and concatenate them
$content = '';

$allFilesIncluded = array_flip(get_included_files());
$fileToModify = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH . 'config/dev/All_Config.php');
$val = $allFilesIncluded[$fileToModify];
unset($allFilesIncluded[$fileToModify]);

$allFilesIncluded[BASE_PATH . 'config/prod/All_Config.php'] = $val;
$filesToConcat = array_diff(array_flip($allFilesIncluded), $firstFilesIncluded);
$filesToConcat[] = BASE_PATH . 'lib\\myLibs\\core\\Lionel_Exception.php';

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require BASE_PATH . 'lib/myLibs/core/console/TaskFileOperation.php';
ksort($filesToConcat);
contentToFile(fixUses($content, $verbose, $filesToConcat), $file_);

unset($content);

if(hasSyntaxErrors($file_, $verbose))
  return;

compressPHPFile($file_, $file);

// Declaration of the special t function for templates...
function t($texte){ echo $texte; }
?>
