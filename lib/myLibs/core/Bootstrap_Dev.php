<?
$_SESSION['debuglp_'] = 'Dev';
define ('BEFORE', microtime(true));
require '../lib/myLibs/core/Debug_Tools.php';

if('out' == $_GET['d'])
	unset($_SESSION['debuglp_']);

use lib\myLibs\core\Router,
    config\Routes,
    lib\myLibs\core\Lionel_Exception,
    config\All_Config;

ob_start();

define('DS', DIRECTORY_SEPARATOR);
ini_set('html_errors', 1);
ini_set('error_reporting', 1);
error_reporting(-1);
require '../lib/myLibs/core/Universal_Loader.php';
function errorHandler($errno, $message, $file, $line, $context) { throw new Lionel_Exception($message, $errno, $file, $line, $context); }
set_error_handler('errorHandler');
define('XMODE', 'dev');

ob_get_clean();

try{
  // if the pattern is in the routes, launch the associated route
  if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    call_user_func('bundles\\' . Routes::$default['bundle'] . '\\Init::Init');
    Router::get($route[0], $route[1]);
  }
}catch(Exception $e){ echo $e->errorMessage(); return false;}
?>
