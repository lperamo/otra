<?php
declare(strict_types=1);

define('ARG_CACHE_PATH', $argv[1]);
define('ARG_SITE_ROUTE', $argv[2]);
define('ARG_SHA_NAME', $argv[3]);

// Fixes windows awful __DIR__
define('_DIR_', realpath(str_replace('\\', '/', __DIR__) . '/../../../../'));
// if true, we are not developing on OTRA itself
define('OTRA_PROJECT', strpos(_DIR_, 'vendor') !== false);
// The path finishes with /
define(
  'BASE_PATH',
  OTRA_PROJECT === true
    ? substr(_DIR_, 0, -16) // 16 = strlen('vendor/otra/otra')
    : _DIR_ . '/'
);

define(
  'CORE_PATH',
  OTRA_PROJECT === true
    ? BASE_PATH . 'vendor/otra/otra/src/'
    : BASE_PATH . 'src/'
);
$_SERVER['APP_ENV'] = 'prod';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';

// Loads the main configuration
require BASE_PATH . 'config/AllConfig.php';

// Loads the production class mapping
require BASE_PATH . 'cache/php/ClassMap.php';

spl_autoload_register(function ($className)
{
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

// Loads router and compression tools
require CORE_PATH . 'Router.php';
require CORE_PATH . 'tools/compression.php';

$_SERVER['REQUEST_URI'] = \otra\Router::getRouteUrl(ARG_SITE_ROUTE);

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
\otra\Router::get(ARG_SITE_ROUTE);
$content = ob_get_clean();

// We restore the error reporting
error_reporting($oldErrorReporting);

// We generate the file and gzip it
$tplPath = ARG_CACHE_PATH . 'tpl/';

if (false === file_exists($tplPath))
  mkdir($tplPath, 0755, true);

$pathAndFile = ARG_CACHE_PATH . 'tpl/' . ARG_SHA_NAME;
file_put_contents($pathAndFile, preg_replace('@\s{2,}@', ' ', $content));
gzCompressFile($pathAndFile, $pathAndFile . '.gz', 9);
