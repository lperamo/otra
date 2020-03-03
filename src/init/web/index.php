<?php
/** Bootstrap of the framework - Production entry point
 *
 * @author Lionel Péramo */
define ('_DIR_', str_replace('\\', '/', __DIR__));

// if true, we are not developing on OTRA itself
define('OTRA_PROJECT', file_exists(_DIR_ . '/../vendor/otra'));

// The path finishes with /
define('BASE_PATH', substr(_DIR_, 0, -3)); // 3 = strlen('web')

define(
  'CORE_PATH',
  OTRA_PROJECT === true
    ? BASE_PATH . 'vendor/otra/otra/src/'
    : BASE_PATH . 'src/'
);

require CORE_PATH . 'entryPoint.php';

// Is it an asset ?
if (isset($posDot) !== false) return 0;

// TODO Find a way to avoid duplication of the definition of the version already present in the config/AllConfig file!
define('VERSION', '1.0.0-alpha.2.1.0');

try
{
  require BASE_PATH . 'cache/php/RouteManagement.php';

  if ($route = \cache\php\Router::getByPattern($uri))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');

    // Is it a static page
    if ('cli' !== PHP_SAPI && true === isset(\cache\php\Routes::$_[$route[0]]['resources']['template']))
    {
      header('Content-Encoding: gzip');
      echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . VERSION . 'che') . '.gz'); // version to change
      exit;
    }

    // Otherwise for dynamic pages...
    $_SERVER['APP_ENV'] = 'prod';

    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    /** CLASS MAPPING */
    require BASE_PATH . 'cache/php/ProdClassMap.php';

    spl_autoload_register(function ($className) {
      if (false === isset(CLASSMAP[$className]))
      {
        require_once CORE_PATH . 'Logger.php';
        \src\Logger::logTo(
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
} catch (Exception $e)
{
  // Logs the error for developers...
  require_once CORE_PATH . 'Logger.php';
  \src\Logger::logTo(
    'Exception : ' . $e->getMessage() . PHP_EOL .
    'Stack trace : ' . PHP_EOL .
    print_r(debug_backtrace(), true),
    'unknownExceptions'
  );

  // and shows a message for users !
  echo 'Server in trouble. Please come back later !';
} catch (Error $e)
{
  // Logs the error for developers...
  require_once CORE_PATH . 'Logger.php';
  \src\Logger::logTo(
    'Fatal error : ' . $e->getMessage() . PHP_EOL .
    'Stack trace : ' . PHP_EOL .
    print_r(debug_backtrace(), true),
    'unknownFatalErrors'
  );

  // and shows a message for users !
  echo 'Server in great trouble. Please come back later !';
}
