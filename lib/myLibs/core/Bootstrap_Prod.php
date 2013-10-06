<?
unset($_SESSION['debuglp_']);
use lib\myLibs\core\Router,
    config\Routes;

ob_start();
define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', substr(__DIR__, 0, -15)); // Finit avec /

require BASE_PATH . 'lib/myLibs/core/Universal_Loader.php';

define('XMODE', 'prod');
ob_get_clean();

// if the pattern is in the routes and not static, launch the associated route
if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
{
  header('Content-Type: text/html; charset=utf-8');
  header("Vary: Accept-Encoding,Accept-Language");

  // if static
  if('cli' != php_sapi_name() && isset(Routes::$_[$route[0]]['resources']['template'])){
    require BASE_PATH . 'config/All_Config.php';
    // var_dump('ca' . $route[0] . VERSION . 'che');die;
    require BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . VERSION . 'che') . '.html';
    die;
  }

  call_user_func('bundles\\' . Routes::$default['bundle'] . '\\Init::Init');
  Router::get($route[0], $route[1]);
}
?>
