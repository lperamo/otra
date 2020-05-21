<?php
declare(strict_types=1);

/** Bootstrap of the framework - Development entry point
 *
 * @author Lionel Péramo */
require __DIR__ . '/../config/constants.php';

$uri = $_SERVER['REQUEST_URI'];
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'cookie_samesite' => 'strict'
]);
define ('BEFORE', microtime(true));

// If it is an asset, we echo it and we stop the work here
if (isset($_ENV['OTRA_LIVE_APP_ENV']) && require CORE_PATH . 'internalServerEntryPoint.php')
  return true;

require CORE_PATH . 'debugTools.php';

ini_set('display_errors', '1');
ini_set('html_errors', '1');
error_reporting(-1 & ~E_DEPRECATED);

/** CLASS MAPPING */
require CACHE_PATH . 'php/ClassMap.php';

/** MAIN CONFIGURATION */
require BASE_PATH . 'config/AllConfig.php';

spl_autoload_register(function(string $className)
{
  if (false === isset(CLASSMAP[$className]))
    echo 'Path not found for the class name : ', $className, '<br>';
  else
    require CLASSMAP[$className];
});

use otra\OtraException;
set_error_handler([OtraException::class, 'errorHandler']);
set_exception_handler([OtraException::class, 'exceptionHandler']);

use otra\Router;

// If the pattern is in the routes, launch the associated route
if ($route = Router::getByPattern($_SERVER['REQUEST_URI']))
{
  header('Content-Type: text/html; charset=utf-8');
  header('Vary: Accept-Encoding,Accept-Language');
  Router::get($route[0], $route[1]);
}
