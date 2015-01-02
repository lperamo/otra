<?
$verbose = $argv[1];
$route = $argv[2];
echo $route;

define('BASE_PATH', substr(str_replace('\\', '/', __DIR__), 0, -15)); // Fixes windows awful __DIR__, BASE_PATH ends with /
define('XMODE', 'dev');

require BASE_PATH . 'config/All_Config.php';
require BASE_PATH . 'config/Routes.php';
require BASE_PATH . 'cache/php/ClassMap.php';
require BASE_PATH . 'lib/myLibs/core/Colors.php';
require BASE_PATH . 'lib/myLibs/core/Router.php';

$defaultRoute = \config\Routes::$default['bundle'];
/* Init require section */
require BASE_PATH . 'bundles/' . $defaultRoute . '/Init.php';
require BASE_PATH . 'lib/myLibs/core/Session.php';
require BASE_PATH . 'lib/myLibs/core/bdd/Sql.php';

$params = \config\Routes::$_[$route];

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
$_SESSION['debuglp_'] = 'Dev';// We save the previous state of dev/prod mode
$_SESSION['filesToConcat'] = array();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);
call_user_func('bundles\\' . $defaultRoute . '\\Init::Init');

// Change the classic autoloader in order to stock file names to concatenate
spl_autoload_register(function($className) use($classMap)
{
  require $classMap[$className];
  $_SESSION['filesToConcat'][] = $classMap[$className];
});

$_SESSION['filesToConcat'][] = BASE_PATH . 'bundles/' . $defaultRoute . '/Init.php';
$_SERVER['REMOTE_ADDR'] = 'console'; // in order to pass some conditions;

// Preparation of default parameters for the routes
$chunks = $params['chunks'];

if(isset($params['post']))
  $_POST = $params['post'];

if(isset($params['get']))
  $_GET = $params['get'];

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
    (isset($params['bootstrap'])) ? $params['bootstrap'] : ''
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

  echo lightGreen(), str_pad('Can execute it.', 35 - strlen($route), ' ', STR_PAD_LEFT), endColor(), PHP_EOL;
} catch(Exception $e)
{
  ob_end_clean();
  echo red(), str_pad('Fail.', 35 - strlen($route), ' ', STR_PAD_LEFT), PHP_EOL,
    str_pad('- ', 5, ' ', STR_PAD_LEFT) . $e->getMessage(), endColor(), PHP_EOL;

  return;
}

// ...and retrieve the PHP classes used and concatenate them
$content = '';
$filesToConcat = $_SESSION['filesToConcat'];

foreach($filesToConcat as $file) { $content .= file_get_contents($file); }

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require BASE_PATH . 'lib/myLibs/core/TaskFileOperation.php';

contentToFile(fixUses($content), $file_);
unset($content);

if(hasSyntaxErrors($file_, $verbose))
  return;

compressPHPFile($file_, $file);

// Declaration of the special t function for templates...
function t($texte){ echo $texte; }
?>
