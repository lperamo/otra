<?
unset($_SESSION['debuglp_']);
use lib\myLibs\core\Router,
    config\Routes;

define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', substr(__DIR__, 0, -15)); // Finit avec /
define('XMODE', 'prod');

require BASE_PATH . 'lib/myLibs/core/ClassMap.php';
spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });

if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
{
  header('Content-Type: text/html; charset=utf-8');
  header('Vary: Accept-Encoding,Accept-Language');

  // if static
  if('cli' != PHP_SAPI && isset(Routes::$_[$route[0]]['resources']['template'])){
    require BASE_PATH . 'config/All_Config.php';
    header('Content-Encoding: gzip');
    require BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . VERSION . 'che') . '.html.gz';
    die;
  }

  // if the pattern is in the routes and not static, launch the associated route
  call_user_func('bundles\\' . Routes::$default['bundle'] . '\\Init::Init');
  Router::get($route[0], $route[1]);
}
