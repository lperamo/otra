<?

use config\AllConfig;

$route = $argv[2] ?? null;

// If we have chosen a specific route
if (isset($route) === true)
{
  $routes = \config\Routes::$_;

  // Is this an existing route ? If not ...
  if (isset($routes[$route]) === false)
  {
    require CORE_PATH . 'console/Tools.php';
    list($newRoute) = guessWords($route, array_keys($routes));

    if ($newRoute === null)
    {
      echo red(), 'The route ', brown(), $route, red(), ' doesn\'t exist.', endColor();

      return null;
    }

    // Otherwise, we suggest the closest name that we have found.
    $choice = promptUser('There is no route named ' . white() . $route . brown(). ' ! Do you mean ' . white() . $newRoute . brown() . ' ? (y/n)');

    if ('n' === $choice)
    {
      echo redText('Sorry then !'), PHP_EOL;
      return null;
    }

    $route = $newRoute;
  }

  $cacheFileName = AllConfig::$cache_path . sha1('ca' . $route . VERSION . 'che');

  // Is there a cache for this route ? If yes, clears it.
  if (file_exists($cacheFileName) === true)
    unlink($cacheFileName);

  echo 'The cache for the route ' . $route . ' has been cleared.', PHP_EOL;

  return null;
}

// Otherwise we clear all the other routes.
array_map('unlink', glob(AllConfig::$cache_path . '*.cache'));
echo 'Cache cleared.', PHP_EOL;

?>
