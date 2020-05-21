<?php
declare(strict_types=1);

/** Bootstrap of the framework - Production entry point
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

// Otherwise for dynamic pages...
$_SERVER['APP_ENV'] = 'prod';

try
{
  require CACHE_PATH . 'php/RouteManagement.php';

  if ($route = \cache\php\Router::getByPattern($uri))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');

    // Is it a static page
    if ('cli' !== PHP_SAPI && true === isset(\cache\php\Routes::$_[$route[0]]['resources']['template']))
    {
      header('Content-Encoding: gzip');
      require BASE_PATH . 'config/AllConfig.php';
      echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . VERSION . 'che') . '.gz'); // version to change
      exit;
    }

    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    /** CLASS MAPPING */
    require CACHE_PATH . 'php/ProdClassMap.php';

    spl_autoload_register(function ($className) {
      if (false === isset(CLASSMAP[$className]))
      {
        require_once CORE_PATH . 'Logger.php';
        \otra\Logger::logTo(
          'Path not found for the class name : ' . $className . PHP_EOL .
          'Stack trace : ' . PHP_EOL .
          print_r(debug_backtrace(), true),
          'classNotFound'
        );
      } else
        require CLASSMAP[$className];
    });

    // Loads the found route
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';

    \cache\php\Router::get($route[0], $route[1]);
  }
} catch (Exception $exception)
{
  require_once CORE_PATH . 'Logger.php';
  \otra\Logger::logExceptionOrErrorTo($exception->getMessage(), 'Exception');
  echo 'Server in trouble. Please come back later !';
} catch (Error $error)
{
  require_once CORE_PATH . 'Logger.php';
  \otra\Logger::logExceptionOrErrorTo($error->getMessage(), 'Fatal error');
  echo 'Server in great trouble. Please come back later !';
}
