<?php
/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genAssets;

use otra\cache\php\Logger;
use otra\Router;
use const otra\cache\php\{APP_ENV, BASE_PATH, CORE_PATH, PROD};
use const otra\cache\php\init\CLASSMAP;
use function otra\tools\gzCompressFile;

$argumentsVector = $argv;
define(__NAMESPACE__ . '\\ARG_CACHE_PATH', $argumentsVector[1]);
define(__NAMESPACE__ . '\\ARG_SITE_ROUTE', $argumentsVector[2]);
define(__NAMESPACE__ . '\\ARG_SHA_NAME', $argumentsVector[3]);

define(__NAMESPACE__ . '\\OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
require __DIR__ . (OTRA_PROJECT
    ? '/../../../../../../..' // long path from vendor
    : '/../../../..'
  ) . '/config/constants.php';
$_SERVER[APP_ENV] = PROD;
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';

// Loads the main configuration
require BASE_PATH . 'config/AllConfig.php';

// Loads the production class mapping
require BASE_PATH . 'cache/php/init/ClassMap.php';

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

// Loads router and compression tools
require CORE_PATH . 'Router.php';
require CORE_PATH . 'tools/compression.php';

$_SERVER['REQUEST_URI'] = Router::getRouteUrl(ARG_SITE_ROUTE);

// We don't allow errors shown on production !
$oldErrorReporting = error_reporting();
error_reporting(0);
ob_start();

// We launch a session in all cases to avoid stuff not loaded based on condition on sessions
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'cookie_samesite' => 'strict'
]);

// We launch the route
Router::get(ARG_SITE_ROUTE);
$content = ob_get_clean();

// We restore the error reporting
error_reporting($oldErrorReporting);

// We generate the file and gzip it
$tplPath = ARG_CACHE_PATH . 'tpl/';

if (!file_exists($tplPath))
  mkdir($tplPath, 0755, true);

$pathAndFile = ARG_CACHE_PATH . 'tpl/' . ARG_SHA_NAME;

// remove extra spaces
$content = preg_replace('@\s{2,}(?![^<]*</pre>)@', ' ', $content);

// strips HTML comments that are not HTML conditional comments and write the content
file_put_contents($pathAndFile, preg_replace('@<!--.*?-->@', '', $content));

gzCompressFile($pathAndFile, $pathAndFile . '.gz');
