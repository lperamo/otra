<?php
declare(strict_types=1);

/** Bootstrap of the framework - Production entry point
 *
 * @author Lionel Péramo */

use cache\php\{Logger,Router};

require __DIR__ . '/../config/constants.php';

$requestUri = $_SERVER['REQUEST_URI'];
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'cookie_samesite' => 'strict'
]);

// Otherwise for dynamic pages...
$_SERVER[APP_ENV] = 'prod';

try
{
  require CACHE_PATH . 'php/RouteManagement.php';

  $route = Router::getByPattern($requestUri);
  define('OTRA_ROUTE', $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME]);

  header('Content-Type: text/html; charset=utf-8');
  header('Vary: Accept-Encoding,Accept-Language');

  // Is it a static page
  if ('cli' !== PHP_SAPI &&
    isset(
      \cache\php\Routes::$_[OTRA_ROUTE]['resources']['template']
    ) && \cache\php\Routes::$_[OTRA_ROUTE]['resources']['template'] === true)
    require BASE_PATH . 'web/loadStaticRoute.php';

  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

  /** CLASS MAPPING */
  require CACHE_PATH . 'php/ProdClassMap.php';

  spl_autoload_register(function ($className)
  {
    if (!isset(CLASSMAP[$className]))
    {
      require_once CORE_PATH . 'Logger.php';
      Logger::logTo(
        'Path not found for the class name : ' . $className . PHP_EOL .
        'Stack trace : ' . PHP_EOL .
        print_r(debug_backtrace(), true),
        'classNotFound'
      );
    } else
      require CLASSMAP[$className];
  });

  // Loads the found route
  require BASE_PATH . 'cache/php/' . OTRA_ROUTE . '.php';

  Router::get(OTRA_ROUTE, $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS]);
} catch (Exception $exception)
{
  define('EXCEPTION_RELATIVE_LOG_PATH', 'logs/' . $_SERVER[APP_ENV] . '/unknownExceptions.txt');
  define('EXCEPTION_LOG_PATH', BASE_PATH . EXCEPTION_RELATIVE_LOG_PATH);
  define('EXCEPTION_TRACE', $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());

  if (is_writable(EXCEPTION_LOG_PATH) === false)
    echo 'Cannot log the exceptions to <span style="color=blue">' . EXCEPTION_RELATIVE_LOG_PATH . '</span> due to a lack of permissions!<br/>';
  elseif (class_exists(\cache\php\Logger::class))
    Logger::logExceptionOrErrorTo(EXCEPTION_TRACE, 'Exception');
  else
    error_log(
      '[' . date(DATE_ATOM, time()) . ']' . ' Route not launched ! Exception : ' . PHP_EOL .
      EXCEPTION_TRACE . PHP_EOL . 'Stack trace : ' . PHP_EOL . print_r(debug_backtrace(), true),
      3,
      EXCEPTION_LOG_PATH
    );

  echo 'Server in trouble. Please come back later !';
} catch (Error $error)
{
  define('ERROR_RELATIVE_LOG_PATH', 'logs/' . $_SERVER[APP_ENV] . '/unknownFatalErrors.txt');
  define('ERROR_LOG_PATH', BASE_PATH . ERROR_RELATIVE_LOG_PATH);
  define('ERROR_TRACE', $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine());

  if (is_writable(ERROR_LOG_PATH) === false)
    echo 'Cannot log the errors to <span style="blue">' . ERROR_RELATIVE_LOG_PATH . '</span> due to a lack of permissions!<br/>';
  elseif (class_exists(\cache\php\Logger::class))
    Logger::logExceptionOrErrorTo(ERROR_TRACE, 'Fatal error');
  else
    error_log(
      '[' . date(DATE_ATOM, time()). ']' . ' Route not launched ! Fatal error : ' . PHP_EOL .
      ERROR_TRACE . PHP_EOL . 'Stack trace : ' . PHP_EOL . print_r(debug_backtrace(), true),
      3,
      ERROR_LOG_PATH
    );

  echo 'Server in great trouble. Please come back later !';
}
