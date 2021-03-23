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

  /** @var array<string,array{
   *   0:string,
   *   1:array{
   *     chunks:array{0:string,1:string,2:string,3:string,4:string},
   *     core?:bool,
   *     resources:array{
   *       template?:bool,
   *       _css?:string[],
   *       _js?:string[],
   *       bundle_css?:string,
   *       bundle_js?:string,
   *       core_css?:string,
   *       core_js?:string
   *     },
   *     bootstrap?:array,
   *     post?:array,
   *     get?:array,
   *     session?:array
   *   }
   * }> \cache\php\Routes::$allRoutes
   */
  // Is it a static page
  if ('cli' !== PHP_SAPI &&
    isset(
      \cache\php\Routes::$allRoutes[OTRA_ROUTE]['resources']['template']
    ) && \cache\php\Routes::$allRoutes[OTRA_ROUTE]['resources']['template'] === true)
    require BASE_PATH . 'web/loadStaticRoute.php';

  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

  /** CLASS MAPPING */
  require CACHE_PATH . 'php/ProdClassMap.php';

  spl_autoload_register(function (string $className) : void
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
} catch (Throwable $issue)
{
  $error = $issue instanceof Error;
  define(
    'ISSUE_RELATIVE_LOG_PATH',
    'logs/' . $_SERVER[APP_ENV] . ($error ? '/unknownFatalErrors.txt' : '/unknownExceptions.txt')
  );
  define('ISSUE_LOG_PATH', BASE_PATH . ISSUE_RELATIVE_LOG_PATH);
  define('ISSUE_TRACE', $issue->getMessage() . ' in ' . $issue->getFile() . ':' . (string)$issue->getLine());

  if (!is_writable(ISSUE_LOG_PATH))
    echo 'Cannot log the ' . ($error ? 'errors' : 'exceptions') . ' to <span style="color:blue">' .
      ISSUE_RELATIVE_LOG_PATH . '</span> due to a lack of permissions!<br/>';
  elseif (class_exists(\cache\php\Logger::class))
    Logger::logExceptionOrErrorTo(ISSUE_TRACE, $error ? 'Error' : 'Exception');
  else
    error_log(
      '[' . date(DATE_ATOM, time()) . ']' . ' Route not launched ! ' .
      ($error ? 'Fatal error' : 'Exception') . ' : ' . PHP_EOL .
      ISSUE_TRACE . PHP_EOL .
      'Stack trace : ' . PHP_EOL .
      print_r(debug_backtrace(), true),
      3,
      ISSUE_LOG_PATH
    );

  echo 'Server in ' . ($error ? 'great ' : '') . 'trouble. Please come back later !';
}
