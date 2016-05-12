<?
declare(strict_types=1);

/**
 * @param array  $array
 * @param string $fileBaseName
 *
 * @return bool|int Index if found, false otherwise
 */
function foundKey(array $array, string $fileBaseName)
{
  return array_search(str_replace('/', DIRECTORY_SEPARATOR, $fileBaseName), $array);
}

$verbose = $argv[1];
$route = $argv[2];

define('BASE_PATH', substr(str_replace('\\', '/', __DIR__), 0, 27)); // Fixes windows awful __DIR__, BASE_PATH ends with /. 27 is strlen(__DIR__) - strlen('lib/myLibs/core/console')
define('CORE_PATH', BASE_PATH . 'lib/myLibs/core/');
require CORE_PATH . 'console/Colors.php';

echo white(), str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL, endColor();
define('XMODE', 'dev');

require BASE_PATH . 'cache/php/ClassMap.php';

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
$_SESSION['debuglp_'] = 'Dev';// We save the previous state of dev/prod mode
$firstFilesIncluded = get_included_files();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);

spl_autoload_register(function($className) use($classMap)
{
  require $classMap[$className];
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

  echo str_pad('Route execution ', 75, '.', STR_PAD_RIGHT), greenText(' [OK]');
} catch(Exception $e)
{
  ob_end_clean();
  echo red(), str_pad('Fail.', 35 - strlen($route), ' ', STR_PAD_LEFT), PHP_EOL,
    str_pad('- ', 5, ' ', STR_PAD_LEFT) . $e->getMessage(), endColor(), PHP_EOL;

  return;
}

// ...and retrieve the PHP classes used and concatenate them
$content = '';

$allFilesIncluded = get_included_files();
$allFilesIncluded[foundKey($allFilesIncluded, BASE_PATH . 'config/dev/All_Config.php')]
  = BASE_PATH . 'config/prod/All_Config.php';

$filesToConcat = array_diff($allFilesIncluded, $firstFilesIncluded);
$filesToConcat[] = BASE_PATH . 'lib\\myLibs\\core\\Lionel_Exception.php';

/** WE UNSET THE ROUTER CLASS AND ROUTES CLASS BECAUSE WE HAVE THEM IN cache/php/RouteManagement.php */
$keyRouter = foundKey($filesToConcat, CORE_PATH . 'Router.php');

if (false !== $keyRouter)
  unset($filesToConcat[$keyRouter]);

$keyRoutes = foundKey($filesToConcat, BASE_PATH . 'config/Routes.php');

if (false !== $keyRoutes)
  unset($filesToConcat[$keyRoutes]);

/** END */

// We fix the created problems, check syntax errors and then minifies it
$file = BASE_PATH . 'cache/php/' . $route;
$file_ = $file . '_.php';

require CORE_PATH . 'console/TaskFileOperation.php';
ksort($filesToConcat);

// TEST WITHOUT TEMPLATE PHTML
foreach($filesToConcat as $key => $fileToConcat)
{
  if (false !== strpos($fileToConcat, '.phtml'))
    unset($filesToConcat[$key]);
}

contentToFile(fixFiles($content, $verbose, $filesToConcat), $file_);

unset($content);

if(hasSyntaxErrors($file_, $verbose))
  return;

compressPHPFile($file_, $file);

// Declaration of the special translation t function for templates...
function t(string $text) : string { return $text; }
?>
