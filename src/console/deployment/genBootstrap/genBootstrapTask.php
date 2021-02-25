<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

namespace otra\console;

use config\{Routes, AllConfig};

// If we come from the deploy task, those two constants are already defined
if (!defined('GEN_BOOTSTRAP_ARG_CLASS_MAPPING'))
{
  define('GEN_BOOTSTRAP_ARG_CLASS_MAPPING', 2);
  define('GEN_BOOTSTRAP_ARG_VERBOSE', 3);
}

define('GEN_BOOTSTRAP_ARG_ROUTE', 4);

define('OTRA_KEY_DRIVER', 'driver');

$verbose = isset($argv[GEN_BOOTSTRAP_ARG_VERBOSE]) ? (int) $argv[GEN_BOOTSTRAP_ARG_VERBOSE] : 0;

// We generate the class mapping file if we need it.
if (false === (isset($argv[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]) && '0' === $argv[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]))
{
  // Generation of the class mapping
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

  // Re-execute our task now that we have a correct class mapping
  require CORE_PATH . 'tools/cli.php';

  [$status, $return] = cli(
    PHP_BINARY . ' ./bin/otra.php genBootstrap 0 ' . $verbose . ' ' . ($argv[GEN_BOOTSTRAP_ARG_ROUTE] ?? '')
  );
  echo $return;

  return $status;
}

if (!isset(AllConfig::$deployment) || !isset(AllConfig::$deployment['domainName']))
{
  echo CLI_RED, 'You must define the ', CLI_LIGHT_CYAN, 'domainName', CLI_RED,
  ' key in the production configuration file to make this task work.', END_COLOR, PHP_EOL, PHP_EOL;
  throw new \otra\OtraException('', 1, '', NULL, [], true);
}

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'Router.php';

// Checks that the folder of micro bootstraps exists
if (!file_exists($bootstrapPath = BASE_PATH . 'cache/php'))
  mkdir($bootstrapPath);

// Checks whether we want only one/many CORRECT route(s)
if (isset($argv[GEN_BOOTSTRAP_ARG_ROUTE]))
{
  $route = $argv[GEN_BOOTSTRAP_ARG_ROUTE];

  if (!isset(Routes::$_[$route]))
  {
    // We try to find a route which the name is similar
    // (require_once 'cause maybe the user type a wrong task like 'genBootstrap' so we have already loaded this src !
    require_once CONSOLE_PATH . 'tools.php';
    list($newRoute) = guessWords($route, array_keys(Routes::$_));

    // And asks the user whether we find what he wanted or not
    $choice = promptUser('There are no route with the name ' . CLI_WHITE . $route . CLI_YELLOW
      . ' ! Do you mean ' . CLI_WHITE . $newRoute . CLI_YELLOW . ' ? (y/n)');

    // If our guess is wrong, we apologise and exit !
    if ('n' === $choice)
    {
      echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
      throw new \otra\OtraException('', 1, '', NULL, [], true);
    }

    $route = $newRoute;
  }

  $routes = [$route => Routes::$_[$route]];
  echo 'Generating \'micro\' bootstrap ...', PHP_EOL, PHP_EOL;
} else
{
  $routes = Routes::$_;
  // otra_exception route has no controller
  unset($routes['otra_exception']);
  echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
}

// In CLI mode, the $_SERVER variable is not set so we set it !
$_SERVER[APP_ENV] = 'prod';

$key = 0;

foreach(array_keys($routes) as $route)
{
  if ('exception' === $route)
    continue;

  if (0 !== $key)
    echo PHP_EOL;

  ++$key;

  if (isset($routes[$route]['resources']['template']) && $routes[$route]['resources']['template'] === true)
    echo CLI_WHITE, str_pad(str_pad(' ' . $route, 25, ' ', STR_PAD_RIGHT) . CLI_CYAN
        . ' [NO MICRO BOOTSTRAP => TEMPLATE GENERATED] ' . CLI_WHITE, 94, '=', STR_PAD_BOTH), END_COLOR, PHP_EOL;
  else
    passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genBootstrap/oneBootstrap.php" ' . $verbose . ' ' . $route);
}

// Final specific management for routes files
echo 'Create the specific routes management file... ', PHP_EOL;

// CACHE_PATH will not be found if we do not have dbConnections in AllConfig so we need to explicitly include the
// configuration. We checks if we do not have already loaded the configuration before.
if (!defined('CORE_VIEWS_PATH'))
  require BASE_PATH . 'config/AllConfig.php';

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    OTRA_KEY_DRIVER => empty(AllConfig::$dbConnections) === false
      && array_key_exists(OTRA_KEY_DRIVER, AllConfig::$dbConnections[key(AllConfig::$dbConnections)]) === true
      ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER]
      : '',
    "_SERVER[APP_ENV]" => $_SERVER[APP_ENV],
    'temporaryEnv' => 'prod'
  ]
);

$routesManagementFile = $bootstrapPath . '/RouteManagement_.php';

require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';

$fileToInclude = CORE_PATH . 'Router.php';

contentToFile(
  fixFiles(
    $routes[$route]['chunks'][1],
    $route,
    file_get_contents($fileToInclude) . '?>',
    $verbose,
    $fileToInclude
  ),
  $routesManagementFile
);

if (hasSyntaxErrors($routesManagementFile))
  return;

compressPHPFile($routesManagementFile, $bootstrapPath . '/RouteManagement');

echo PHP_EOL;
