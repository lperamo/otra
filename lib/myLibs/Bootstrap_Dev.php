<?
$_SESSION['debuglp_'] = 'Dev';

define ('BEFORE', microtime(true));

if (false === defined('BASE_PATH'))
  define('BASE_PATH', substr(__DIR__, 0, -15)); // Ends with /

require CORE_PATH . 'Debug_Tools.php';

// User wants to get out from the dev mode, so we do it and we refresh (more a redirect) the page
if (true === isset($_GET['d']) && 'out' === $_GET['d'])
{
  unset($_SESSION['debuglp_']);
  header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REDIRECT_URL']);
}

ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('error_reporting', -1 & ~E_DEPRECATED);

// We are now in dev mode
define('XMODE', 'dev');

/** CLASS MAPPING */
require BASE_PATH . 'cache/php/ClassMap.php';

/** MAIN CONFIGURATION */
require BASE_PATH . 'config/All_Config.php';

spl_autoload_register(function(string $className)
{
  if (false === isset(CLASSMAP[$className]))
    echo 'Path not found for the class name : ', $className, '<br>';
  else
    require CLASSMAP[$className];
});

/** ERROR MANAGEMENT */
function errorHandler(int $errno, string $message, string $file, int $line, array $context) { throw new lib\myLibs\Lionel_Exception($message, $errno, $file, $line, $context); }

set_error_handler('errorHandler');

use lib\myLibs\Router;

try
{
  // If the pattern is in the routes, launch the associated route
  if ($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');

    $defaultRoute = config\Routes::$default['bundle'];
    Router::get($route[0], $route[1]);
  }
} catch(Exception $e)
{
  echo (true === isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
    ? '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}'
    : $e->getMessage();

  exit(1);
}
