<?php
/** Bootstrap of the framework - Production entry point
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra\web;

use Error;
use otra\cache\php\{Logger, Router, Routes};
use Throwable;
use const otra\cache\php\{APP_ENV,BASE_PATH, CACHE_PATH, CORE_PATH,PROD};
use const otra\cache\php\CLASSMAP;

require __DIR__ . '/../config/constants.php';

$requestUri = $_SERVER['REQUEST_URI'];

// Otherwise, for dynamic pages...
$_SERVER[APP_ENV] = PROD;

try
{
  require CACHE_PATH . 'php/init/RouteManagement.php';

  $route = Router::getByPattern($requestUri);
  define(__NAMESPACE__ . '\\OTRA_ROUTE', $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME]);

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

  // Is it a static page?
  if ('cli' !== PHP_SAPI &&
    isset(
      Routes::$allRoutes[OTRA_ROUTE]['resources']['template']
    ) && Routes::$allRoutes[OTRA_ROUTE]['resources']['template'] === true)
    require BASE_PATH . 'web/loadStaticRoute.php';

  ini_set('session.save_path', CACHE_PATH . 'php/sessions/');
  // Put this "cache_limiter" option also sets the header 'Cache-Control: private, max-age=10800'
  ini_set('session.cache_limiter', 'private');
  // Prevents from having thousands of session files that dramatically break performance when we use a JMeter test with
  // thousands of iterations
  if (isset($_SERVER['JMETER']) && $_SERVER['JMETER'] === 'test')
  {
    ini_set('session.gc_maxlifetime', 2);
    session_gc();
  }

  session_name('__Secure-LPSESSID');
  session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'strict'
  ]);

  // Prevents the cache from disturbing the statistics when doing JMeter tests
  if (isset($_SERVER['JMETER']) && $_SERVER['JMETER'] === 'test')
    header('Cache-Control: no-cache');

  header_remove('Expires');

  error_reporting(E_ALL);

  /** CLASS MAPPING */
  require CACHE_PATH . 'php/init/ProdClassMap.php';

  spl_autoload_register(function (string $className) : void
  {
    if (isset(CLASSMAP[$className]))
    {
      require CLASSMAP[$className];
      return;
    }

    if (!class_exists(Logger::class))
      require_once CORE_PATH . 'Logger.php';

    Logger::logWithStackTraces('Path not found for the class name : ' . $className, debug_backtrace());
  });

  // Loads the found route
  require BASE_PATH . 'cache/php/' . (str_contains(OTRA_ROUTE, 'otra_') ? 'otraRoutes/' : '') . OTRA_ROUTE . '.php';

  Router::get(OTRA_ROUTE, $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS]);
} catch (Throwable $issue)
{
  $error = $issue instanceof Error;
  define(
    __NAMESPACE__ . '\\ISSUE_RELATIVE_LOG_PATH',
    'logs/' . $_SERVER[APP_ENV] . ($error ? '/unknownFatalErrors.txt' : '/unknownExceptions.txt')
  );
  define(__NAMESPACE__ . '\\ISSUE_LOG_PATH', BASE_PATH . ISSUE_RELATIVE_LOG_PATH);
  define(__NAMESPACE__ . '\\ISSUE_TRACE', $issue->getMessage() . ' in ' . $issue->getFile() . ':' . $issue->getLine());

  if (!is_writable(ISSUE_LOG_PATH))
    echo 'Cannot log the ' . ($error ? 'errors' : 'exceptions') . ' to <span style="color: blue;">' .
      ISSUE_RELATIVE_LOG_PATH . '</span> due to a lack of permissions!<br/>';
  elseif (class_exists(Logger::class))
    Logger::logExceptionOrErrorTo(
      ISSUE_TRACE,
      $error ? 'Error' : 'Exception',
      $issue->getTrace()
    );
  else
    error_log(
      json_encode(
        [
          'd' => date(DATE_ATOM, time()),
          'm' => 'Route not launched ! ' . ($error ? 'Fatal error' : 'Exception') . ' : ' . PHP_EOL . ISSUE_TRACE,
          's' => Logger::formatStackTracesForLog($issue->getTrace())
        ],
        Logger::LOG_JSON_MASK
      ),
      3,
      ISSUE_LOG_PATH
    );

  // If no HTTP status code has been set, correct the default 200 to a 500 for critical errors.
  if (http_response_code() === 200)
    http_response_code(500);
  
  echo 'Server in ' . ($error ? 'great ' : '') . 'trouble. Please come back later !';
}
