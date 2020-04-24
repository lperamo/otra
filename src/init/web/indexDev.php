<?php
/** Bootstrap of the framework - Development entry point
 *
 * @author Lionel Péramo */
define('_DIR_', str_replace('\\', '/', __DIR__));

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

define ('BEFORE', microtime(true));

if (isset($_ENV['OTRA_APP_ENV']) === true)
  $_SERVER['APP_ENV'] = $_ENV['OTRA_APP_ENV'];

require CORE_PATH . 'debugTools.php';

ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('error_reporting', -1 & ~E_DEPRECATED);

/** CLASS MAPPING */
require BASE_PATH . 'cache/php/ClassMap.php';

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
