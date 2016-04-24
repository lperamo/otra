<?
// We generate the class mapping file if we need it.
if(!(isset($argv[2]) && '0' == $argv[2]))
{
  require(BASE_PATH . 'lib/myLibs/core/console/GenClassMap.php');
  echo PHP_EOL;
  require BASE_PATH . '/cache/php/ClassMap.php'; // on recharge la classmap que si elle a été modifiée.
}

$verbose = isset($argv[3]) ? $argv[3] : 0;

require BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/core/Router.php';

// Checks that the folder of micro bootstraps exists
if(!file_exists($bootstrapPath = BASE_PATH . 'cache/php'))
  mkdir($bootstrapPath);

// Checks whether we want only one/many CORRECT route(s)
if(isset($argv[4]))
{
  $route = $argv[4];
  if(isset(\config\Routes::$_[$route]))
    $routes = [$route => \config\Routes::$_[$route]];
  else
  {
    echo 'This route doesn\'t exist !', PHP_EOL;
    return;
  }
  echo 'Generating \'micro\' bootstrap for the route \'', $route, '\'', PHP_EOL, PHP_EOL;
} else {
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

  if (isset($routes[$route]['resources']['template']))
  {
    echo white(), $route, cyanText(str_pad('[NO MICRO BOOTSTRAP => TEMPLATE GENERATED]', 66 - strlen($route), ' ', STR_PAD_LEFT));
  } else
    passthru('php ' . BASE_PATH . 'lib/myLibs/core/console/OneBootstrap.php ' . $verbose . ' ' . $route);
}
die;
// Final specific management for routes files
echo 'Create the specific routes management file... ';

$routesManagementFile = $bootstrapPath . '/RouteManagement_.php';

require BASE_PATH . 'lib/myLibs/core/console/TaskFileOperation.php';

contentToFile(
  fixUses(
  file_get_contents(BASE_PATH . '/lib/myLibs/core/Router.php') .
  file_get_contents(BASE_PATH . '/config/Routes.php'),
    $verbose),
   $routesManagementFile);

if(hasSyntaxErrors($routesManagementFile, $verbose))
  return;

compressPHPFile($routesManagementFile, $bootstrapPath . '/RouteManagement');

echo PHP_EOL, 'Generation of the associated templates...' , PHP_EOL;
passthru('php console.php genAssets 1 ' . (isset($argv[4]) ? $argv[4] : ''));
?>
