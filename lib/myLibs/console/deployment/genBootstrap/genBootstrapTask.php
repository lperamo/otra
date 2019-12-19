<?php
declare(strict_types=1);
namespace lib\myLibs\console;

$verbose = isset($argv[3]) === true ? (int) $argv[3] : 0;

// We generate the class mapping file if we need it.
if (false === (isset($argv[2]) === true && '0' == $argv[2]))
{
  // Generation of the class mapping
  require CORE_PATH . 'console/deployment/genClassMap/genClassMapTask.php';

  // Re-execute our task now that we have a correct class mapping
  require CORE_PATH . 'tools/Cli.php';

  list($status, $return) = cli(PHP_BINARY . ' ./otra.php genBootstrap 0 ' . $verbose . ' ' . ($argv[4] ?? ''));
  echo $return;

  return $status;
}

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'Router.php';

// Checks that the folder of micro bootstraps exists
if (false === file_exists($bootstrapPath = BASE_PATH . 'cache/php'))
  mkdir($bootstrapPath);

// Checks whether we want only one/many CORRECT route(s)
if (true === isset($argv[4]))
{
  $route = $argv[4];

  if (false === isset(\config\Routes::$_[$route]))
  {
    // We try to find a route which the name is similar
    // (require_once 'cause maybe the user type a wrong task like 'genBootsrap' so we have already loaded this lib !
    require_once CORE_PATH . 'console/Tools.php';
    list($newRoute) = guessWords($route, array_keys(\config\Routes::$_));

    // And asks the user whether we find what he wanted or not
    $choice = promptUser('There are no route with the name ' . CLI_WHITE . $route . CLI_YELLOW
      . ' ! Do you mean ' . CLI_WHITE . $newRoute . CLI_YELLOW . ' ? (y/n)');

    // If our guess is wrong, we apologise and exit !
    if ('n' === $choice)
    {
      echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
      exit(1);
    }

    $route = $newRoute;
  }

  $routes = [$route => \config\Routes::$_[$route]];
  echo 'Generating \'micro\' bootstrap ...', PHP_EOL, PHP_EOL;
} else
{
  $routes = \config\Routes::$_;
  echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
}

// In CLI mode, the $_SERVER variable is not set so we set it !
$_SERVER['APP_ENV'] = 'prod';

$key = 0;

foreach(array_keys($routes) as &$route)
{
  if ('exception' === $route)
    continue;

  if (0 !== $key)
    echo PHP_EOL;

  ++$key;

  if (true === isset($routes[$route]['resources']['template']))
    echo CLI_WHITE, str_pad(str_pad(' ' . $route, 25, ' ', STR_PAD_RIGHT) . CLI_CYAN
        . ' [NO MICRO BOOTSTRAP => TEMPLATE GENERATED] ' . CLI_WHITE, 94, '=', STR_PAD_BOTH), END_COLOR, PHP_EOL;
  else
    passthru(PHP_BINARY . ' "' . CORE_PATH . 'console/deployment/genBootstrap/oneBootstrap.php" ' . $verbose . ' ' . $route);
}

// Final specific management for routes files
echo 'Create the specific routes management file... ', PHP_EOL;

// CACHE_PATH will not be found if we do not have dbConnections in AllConfig so we need to explicitly include the
// configuration
require BASE_PATH . 'config/AllConfig.php';

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    'driver' => empty(\config\AllConfig::$dbConnections) === false
      && array_key_exists('driver', \config\AllConfig::$dbConnections[key(\config\AllConfig::$dbConnections)]) === true
      ? \config\AllConfig::$dbConnections[key(\config\AllConfig::$dbConnections)]['driver']
      : '',
    "_SERVER['APP_ENV']" => $_SERVER['APP_ENV']
  ]
);

$routesManagementFile = $bootstrapPath . '/RouteManagement_.php';

require CORE_PATH . 'console/deployment/genBootstrap/taskFileOperation.php';

$fileToInclude = CORE_PATH . 'Router.php';

contentToFile(
  fixFiles(
    $routes[$route]['chunks'][1],
    $route,
    file_get_contents($fileToInclude),
    $verbose,
    $fileToInclude
  ),
  $routesManagementFile
);

if (true === hasSyntaxErrors($routesManagementFile))
  return;

compressPHPFile($routesManagementFile, $bootstrapPath . '/RouteManagement');

echo PHP_EOL;
?>
