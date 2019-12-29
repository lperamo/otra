<?php
define ('BEFORE', microtime(true));

if (false === defined('BASE_PATH'))
  define('BASE_PATH', substr(__DIR__, 0, -15)); // Ends with /

require CORE_PATH . 'DebugTools.php';

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

/** ERROR MANAGEMENT
 *
 * @param int    $errno
 * @param string $message
 * @param string $file
 * @param int    $line
 * @param array  $context
 *
 * @throws OtraException
 */
function errorHandler(int $errno, string $message, string $file, int $line, ?array $context) { throw new lib\myLibs\OtraException($message, $errno, $file, $line, $context); }

set_error_handler('errorHandler');

use lib\myLibs\OtraException;
use lib\myLibs\Router;

try
{
  // If the pattern is in the routes, launch the associated route
  if ($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');
    Router::get($route[0], $route[1]);
  }
} catch(Exception $e) // in order to catch fatal errors
{
  if (true === isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
    // json sent if it was an AJAX request
    echo '{"success": "exception", "msg":' . json_encode(new OtraException($e->getMessage())) . '}';
  else
    throw new OtraException($e->getMessage());

  exit(1);
} catch(Error $e)
{
  if (true === isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
    // json sent if it was an AJAX request
    echo '{"success": "exception", "msg":' . json_encode(new OtraException($e->getMessage())) . '}';
  else
    throw new OtraException($e->getMessage());
  exit(1);
}
