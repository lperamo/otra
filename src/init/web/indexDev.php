<?php
/** Bootstrap of the framework - Development entry point
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra\web;

use const otra\cache\php\{BASE_PATH,CACHE_PATH,CORE_PATH};
use const otra\cache\php\CLASSMAP;

require __DIR__ . '/../config/constants.php';
ini_set('session.save_path', CACHE_PATH . 'php/sessions/');
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'cookie_samesite' => 'strict'
]);
define ('BEFORE', microtime(true));

// If it is an asset, we echo it, and we stop the work here
if (isset($_ENV['OTRA_LIVE_APP_ENV']) && require CORE_PATH . 'internalServerEntryPoint.php')
  return true;

ini_set('display_errors', '1');
ini_set('html_errors', '1');
error_reporting(-1);

/** CLASS MAPPING */
require CACHE_PATH . 'php/init/ClassMap.php';

/** MAIN CONFIGURATION */
require BASE_PATH . 'config/AllConfig.php';

spl_autoload_register(function(string $className) : void
{
  if (!isset(CLASSMAP[$className]))
    echo 'Path not found for the class name : ', $className, '<br>';
  else
    require CLASSMAP[$className];
});

use otra\OtraException;
set_error_handler(OtraException::errorHandler(...));
set_exception_handler(OtraException::exceptionHandler(...));

use otra\Router;

// If the pattern is in the routes, launch the associated route
if ($route = Router::getByPattern($_SERVER['REQUEST_URI']))
{
  header('Content-Type: text/html; charset=utf-8');
  header('Vary: Accept-Encoding,Accept-Language');

  Router::get(
    $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME],
    $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS]
  );
}
