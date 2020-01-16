<?php
/** Bootstrap of the framework - Development entry point
 *
 * @author Lionel Péramo */
define('_DIR_', str_replace('\\', '/', __DIR__));
define('BASE_PATH', substr(_DIR_, 0, -3)); // Ends with /

require BASE_PATH . 'lib/otra/entryPoint.php';

define ('BEFORE', microtime(true));

if (false === defined('BASE_PATH'))
  define('BASE_PATH', substr(__DIR__, 0, -15)); // Ends with /

define('CORE_PATH', BASE_PATH . 'lib/otra/'); // Ends with /

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

use lib\otra\OtraException;
set_error_handler([OtraException::class, 'errorHandler']);
set_exception_handler([OtraException::class, 'exceptionHandler']);

use lib\otra\Router;

// If the pattern is in the routes, launch the associated route
if ($route = Router::getByPattern($_SERVER['REQUEST_URI']))
{
  header('Content-Type: text/html; charset=utf-8');
  header('Vary: Accept-Encoding,Accept-Language');
  Router::get($route[0], $route[1]);
}
