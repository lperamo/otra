<?
$_SESSION['debuglp_'] = 'Dev';
define ('BEFORE', microtime(true));
if(!defined('BASE_PATH'))
  define('BASE_PATH', substr(__DIR__, 0, -15)); // Finit avec /

require '../lib/myLibs/core/Debug_Tools.php';

if(isset($_GET['d']) && 'out'== $_GET['d'])
	unset($_SESSION['debuglp_']);

use lib\myLibs\core\Router,
    config\Routes,
    lib\myLibs\core\Lionel_Exception,
    config\All_Config;

ob_start();

define('DS', DIRECTORY_SEPARATOR);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('error_reporting', -1);
error_reporting(-1);
// We load the class mapping
require BASE_PATH . 'cache/php/ClassMap.php';
spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });

function errorHandler($errno, $message, $file, $line, $context) { throw new Lionel_Exception($message, $errno, $file, $line, $context); }
set_error_handler('errorHandler');
define('XMODE', 'dev');

ob_get_clean();

function t($texte){ echo $texte; }

try{
  header('Content-Type: text/html; charset=utf-8');
  header("Vary: Accept-Encoding,Accept-Language");

  // if the pattern is in the routes, launch the associated route
  if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    call_user_func('bundles\\' . Routes::$default['bundle'] . '\\Init::Init');
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';
    Router::get($route[0], $route[1]);

  }
}catch(Exception $e){
  echo $e->errorMessage();
  return false;
}
