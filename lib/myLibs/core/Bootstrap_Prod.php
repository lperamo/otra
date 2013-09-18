<?
unset($_SESSION['debuglp_']);
use config\Router;

ob_start();
ini_set('display_errors', 0);
set_error_handler('errorHandler');
define('DS', DIRECTORY_SEPARATOR);
require '../lib/myLibs/core/Universal_Loader.php';
require '../lib/myLibs/core/Debug_Tools.php';

define('XMODE', 'prod');
ob_get_clean();

try{
  // if the pattern is in the routes, launch the associated route
  if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    call_user_func('bundles\\' . Router::$defaultRoute['bundle'] . '\\Init::Init');
    Router::get($route[0], $route[1]);
  }
}catch(Exception $e){ echo $e->errorMessage(); return false;}
?>
