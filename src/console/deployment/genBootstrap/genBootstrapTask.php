<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap;

use FilesystemIterator;
use otra\OtraException;
use otra\config\AllConfig;
use otra\config\Routes;
use function otra\console\{guessWords,promptUser};
use function otra\tools\cliCommand;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, PROD};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use const otra\bin\CACHE_PHP_INIT_PATH;

if (!file_exists(BUNDLES_PATH) || !(new FilesystemIterator(BUNDLES_PATH))->valid())
{
  echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

// If we come from the deploy task, those two constants are already defined
const
  GEN_BOOTSTRAP_ARG_CLASS_MAPPING = 2,
  GEN_BOOTSTRAP_ARG_VERBOSE = 3,
  GEN_BOOTSTRAP_ARG_LINT = 4,
  GEN_BOOTSTRAP_ARG_ROUTE = 5,
  OTRA_KEY_DRIVER = 'driver';

define(__NAMESPACE__ . '\\GEN_BOOTSTRAP_LINT', isset($argv[GEN_BOOTSTRAP_ARG_LINT]) && $argv[GEN_BOOTSTRAP_ARG_LINT] === '1');
define(__NAMESPACE__ . '\\VERBOSE', isset($argv[GEN_BOOTSTRAP_ARG_VERBOSE]) ? (int) $argv[GEN_BOOTSTRAP_ARG_VERBOSE] : 0);

// We generate the class mapping file if we need it.
if (!(isset($argv[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]) && '0' === $argv[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]))
{
  // Generation of the class mapping
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

  // Re-execute our task now that we have a correct class mapping
  require CORE_PATH . 'tools/cli.php';

  [$status, $return] = cliCommand(
    PHP_BINARY . ' ./bin/otra.php genBootstrap 0 ' . VERBOSE . ' ' . intval(GEN_BOOTSTRAP_LINT) .
    ' ' . ($argv[GEN_BOOTSTRAP_ARG_ROUTE] ?? '')
  );
  echo $return;

  return $status;
}

if (!isset(AllConfig::$deployment) || !isset(AllConfig::$deployment['domainName']))
{
  echo CLI_ERROR, 'You must define the ', CLI_INFO_HIGHLIGHT, 'domainName', CLI_ERROR,
  ' key in the production configuration file to make this task work.', END_COLOR, PHP_EOL, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'Router.php';

const BOOTSTRAP_PATH = BASE_PATH . 'cache/php';

// Checks that the folder of micro bootstraps exists
if (!file_exists(BOOTSTRAP_PATH))
  mkdir(BOOTSTRAP_PATH);

// Checks whether we want only one/many CORRECT route(s)
if (isset($argv[GEN_BOOTSTRAP_ARG_ROUTE]))
{
  $route = $argv[GEN_BOOTSTRAP_ARG_ROUTE];

  if (!isset(Routes::$allRoutes[$route]))
  {
    // We try to find a route which the name is similar
    // (require_once 'cause maybe the user type a wrong task like 'genBootstrap' so we have already loaded this src !
    require_once CONSOLE_PATH . 'tools.php';
    [$newRoute] = guessWords($route, array_keys(Routes::$allRoutes));

    // And asks the user whether we find what he wanted or not
    $choice = promptUser('There are no route with the name ' . CLI_BASE . $route . CLI_WARNING
      . ' ! Do you mean ' . CLI_BASE . $newRoute . CLI_WARNING . ' ? (y/n)');

    // If our guess is wrong, we apologise and exit !
    if ('n' === $choice)
    {
      echo CLI_ERROR, 'Sorry then !', END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', NULL, [], true);
    }

    $route = $newRoute;
  }

  $routes = [$route => Routes::$allRoutes[$route]];
  echo 'Generating \'micro\' bootstrap ...', PHP_EOL, PHP_EOL;
} else
{
  $routes = Routes::$allRoutes;
  // otra_exception route has no controller
  unset($routes['otra_exception']);
  echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
}

// In CLI mode, the $_SERVER variable is not set so we set it !
$_SERVER[APP_ENV] = PROD;

foreach(array_keys($routes) as $routeKey => $route)
{
  if ('exception' === $route)
    continue;

  if (array_key_first($routes) !== $routeKey)
    echo PHP_EOL;

  if (isset($routes[$route]['resources']['template']) && $routes[$route]['resources']['template'] === true)
    echo CLI_BASE, str_pad(str_pad(' ' . $route, 25, ' ', STR_PAD_RIGHT) . CLI_INFO
        . ' [NO MICRO BOOTSTRAP => TEMPLATE GENERATED] ' . CLI_BASE, 94, '=', STR_PAD_BOTH), END_COLOR, PHP_EOL;
  else
    passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genBootstrap/oneBootstrap.php" ' . VERBOSE . ' ' .
      intval(GEN_BOOTSTRAP_LINT) . ' ' . $route);
}

// Final specific management for routes files
echo 'Create the specific routes management file... ', PHP_EOL;

// CACHE_PATH will not be found if we do not have dbConnections in AllConfig so we need to explicitly include the
// configuration. We checks if we do not have already loaded the configuration before.
define(
  __NAMESPACE__ . '\\PATH_CONSTANTS',
  [
    'externalConfigFile' => BUNDLES_PATH . 'config/Config.php',
    OTRA_KEY_DRIVER => !empty(AllConfig::$dbConnections)
      && array_key_exists(OTRA_KEY_DRIVER, AllConfig::$dbConnections[key(AllConfig::$dbConnections)])
      ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER]
      : '',
    "_SERVER[APP_ENV]" => $_SERVER[APP_ENV],
    'temporaryEnv' => PROD
  ]
);
const ROUTE_MANAGEMENT_TEMPORARY_FILE = CACHE_PHP_INIT_PATH . 'RouteManagement_.php';
require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
$fileToInclude = CORE_PATH . 'Router.php';

contentToFile(
  fixFiles(
    $routes[$route]['chunks'][Routes::ROUTES_CHUNKS_BUNDLE],
    $route,
    file_get_contents($fileToInclude) . PHP_END_TAG_STRING,
    VERBOSE,
    $fileToInclude
  ),
  ROUTE_MANAGEMENT_TEMPORARY_FILE
);

if (GEN_BOOTSTRAP_LINT && hasSyntaxErrors(ROUTE_MANAGEMENT_TEMPORARY_FILE))
  return;

compressPHPFile(ROUTE_MANAGEMENT_TEMPORARY_FILE, CACHE_PHP_INIT_PATH . 'RouteManagement');

echo PHP_EOL;
