<?
declare(strict_types=1);
namespace lib\myLibs\console;

$verbose = isset($argv[3]) === true ? (int) $argv[3] : 0;

// We generate the class mapping file if we need it.
if (false === (isset($argv[2]) === true && '0' == $argv[2]))
{
  // Generation of the class mapping
  Tasks::genClassMap([null, null, $verbose]);

  // Re-execute our task now that we have a correct class mapping
  echo PHP_EOL;
  require CORE_PATH . 'console/Tools.php';

  list($status, $return) = cli(PHP_BINARY . ' ./console.php genBootstrap 0 ' . $verbose . ' ' . ($argv[4] ?? ''));
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
  if (true === isset(\config\Routes::$_[$route]))
    $routes = [$route => \config\Routes::$_[$route]];
  else
  {
    echo 'This route doesn\'t exist !', PHP_EOL;
    return;
  }
  echo 'Generating \'micro\' bootstrap ...', PHP_EOL, PHP_EOL;
} else
{
  $routes = \config\Routes::$_;
  echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
}

$key = 0;

foreach(array_keys($routes) as &$route)
{
  if ('exception' === $route)
    continue;

  if (0 !== $key)
    echo PHP_EOL;

  ++$key;

  if (true === isset($routes[$route]['resources']['template']))
    echo white() . str_pad(str_pad(' ' . $route, 25, ' ', STR_PAD_RIGHT) . cyan() . ' [NO MICRO BOOTSTRAP => TEMPLATE GENERATED] ' . white(), 94, '=', STR_PAD_BOTH), endColor(), PHP_EOL;
  else
    passthru(PHP_BINARY . ' "' . CORE_PATH . 'console/OneBootstrap.php" ' . $verbose . ' ' . $route);
}

// Final specific management for routes files
echo 'Create the specific routes management file... ';

define(
  'PATH_CONSTANTS',
  [
    'externalConfigFile' => BASE_PATH . 'bundles/config/Config.php',
    'driver' => \config\AllConfig::$dbConnections[key(\config\AllConfig::$dbConnections)]['driver']
  ]
);

$routesManagementFile = $bootstrapPath . '/RouteManagement_.php';

require CORE_PATH . 'console/TaskFileOperation.php';

contentToFile(
  fixFiles(
    $routes[$route]['chunks'][1],
    $route,
    file_get_contents(CORE_PATH . 'Router.php') . file_get_contents(BASE_PATH . 'config/Routes.php'),
    $verbose
  ),
  $routesManagementFile
);

if (true === hasSyntaxErrors($routesManagementFile, $verbose))
  return;

compressPHPFile($routesManagementFile, $bootstrapPath . '/RouteManagement');

//echo PHP_EOL, 'Generation of the associated templates...' , PHP_EOL;
//passthru('php console.php genAssets 1 ' . (isset($argv[4]) ? $argv[4] : ''));
?>
