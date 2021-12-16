<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap;

use otra\{OtraException, Router};
use otra\config\{AllConfig, Routes};
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CLASS_MAP_PATH, CONSOLE_PATH, CORE_PATH, PROD};
use const otra\cache\php\init\CLASSMAP;
use const otra\console\{CLI_BASE, CLI_ERROR, END_COLOR};
use function otra\tools\files\compressPHPFile;

const
  ONE_BOOTSTRAP_ARG_VERBOSE = 1,
  ONE_BOOTSTRAP_ARG_LINT = 2,
  ONE_BOOTSTRAP_ARG_ROUTE = 3,

  OTRA_KEY_BOOTSTRAP = 'bootstrap',
  OTRA_KEY_DRIVER = 'driver';
define(__NAMESPACE__ . '\\GEN_BOOTSTRAP_LINT', $argv[ONE_BOOTSTRAP_ARG_LINT] === '1');
define(__NAMESPACE__ . '\\VERBOSE', intval($argv[ONE_BOOTSTRAP_ARG_VERBOSE]));
define(__NAMESPACE__ . '\\OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
$route = $argv[ONE_BOOTSTRAP_ARG_ROUTE];
require __DIR__ . (OTRA_PROJECT
    ? '/../../../../../../..' // long path from vendor
    : '/../../../..'
  ) . '/config/constants.php';
require CONSOLE_PATH . 'colors.php';

echo CLI_BASE, str_pad(' ' . $route . ' ', 80, '=', STR_PAD_BOTH), PHP_EOL, PHP_EOL,
  END_COLOR;
$_SERVER[APP_ENV] = PROD;

require CLASS_MAP_PATH;

$_SESSION[OTRA_KEY_BOOTSTRAP] = 1; // in order to not really make BDD requests !
$firstFilesIncluded = get_included_files();

// Force to show all errors
error_reporting(-1 & ~E_DEPRECATED);

spl_autoload_register(function(string $className) : void
{
  if (isset(CLASSMAP[$className]))
  {
    require CLASSMAP[$className];
  } else {
    echo CLI_ERROR, 'CLASSMAP PROBLEM !!', PHP_EOL;
    debug_print_backtrace();
    echo PHP_EOL;
    require CORE_PATH . 'tools/debug/dump.php';
    dump(CLASSMAP);
    echo PHP_EOL, END_COLOR;
  }
});

require BASE_PATH . 'config/AllConfig.php';

$params = Routes::$allRoutes[$route];

// Init require section
require CORE_PATH . 'Session.php';
require CORE_PATH . 'bdd/Sql.php';

// in order to pass some conditions
$_SERVER['REMOTE_ADDR'] = 'console';
$_SERVER['REQUEST_SCHEME'] = 'HTTPS';
$_SERVER['HTTP_HOST'] = AllConfig::$deployment['domainName'];

// Preparation of default parameters for the routes
if (isset($params['post']))
  $_POST = $params['post'];

if (isset($params['get']))
  $_GET = $params['get'];

// We put default parameters in order to not write too much times the session configuration in the routes file
$_SESSION['sid'] = ['uid' => 1, 'role' => 1];

if (isset($params['session']))
{
  foreach($params['session'] as $sessionKey => $param)
  {
    $_SESSION[$sessionKey] = $param;
  }
}

$phpRouteFile = BASE_PATH . (!str_contains($route, 'otra_'))
  ? 'cache/php/' . $route
  : 'cache/php/otraRoutes/' . $route;
$temporaryPhpRouteFile = $phpRouteFile . '_.php';

require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';

// If it is an OTRA core route, we must change the path src from the config/Routes.php file by vendor/otra/otra/src
if (isset($params['core']) && $params['core'])
{
  $fileToInclude = substr(CORE_PATH,0, -5) . str_replace(
      ['\\', 'otra'],
      ['/', 'src'],
      Router::get(
        $route,
        (isset($params[OTRA_KEY_BOOTSTRAP])) ? $params[OTRA_KEY_BOOTSTRAP] : [],
        false
      )
    ) . '.php';
} else
{
  $fileToInclude = BASE_PATH . str_replace(
      '\\',
      '/',
      Router::get(
        $route,
        (isset($params[OTRA_KEY_BOOTSTRAP])) ? $params[OTRA_KEY_BOOTSTRAP] : [],
        false
      )
    ) . '.php';
}

define(
  __NAMESPACE__ . '\\PATH_CONSTANTS',
  [
    'externalConfigFile' => BUNDLES_PATH . 'config/Config.php',
    OTRA_KEY_DRIVER => !empty(AllConfig::$dbConnections)
      && isset(AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER])
      ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER]
      : '',
    '_SERVER[APP_ENV]' => $_SERVER[APP_ENV],
    'temporaryEnv' => PROD
  ]
);

set_error_handler(function (int $errno, string $message, string $file, int $line, ?array $context = null) : void
{
  throw new OtraException($message, $errno, $file, $line, $context);
});

$chunks = $params['chunks'];

// For the moment, as a workaround, we will temporarily explicitly add the OtraException file to solve issues.
contentToFile(
  fixFiles(
    $chunks[Routes::ROUTES_CHUNKS_BUNDLE],
    $route,
    file_get_contents(CORE_PATH . 'OtraException.php') . PHP_END_TAG_STRING .
    file_get_contents($fileToInclude),
    VERBOSE,
    $fileToInclude
  ),
  $temporaryPhpRouteFile
);

if (GEN_BOOTSTRAP_LINT && hasSyntaxErrors($temporaryPhpRouteFile))
  return;

require CORE_PATH . 'tools/files/compressPhpFile.php';
compressPHPFile($temporaryPhpRouteFile, $phpRouteFile . '.php');
unlink($temporaryPhpRouteFile);
