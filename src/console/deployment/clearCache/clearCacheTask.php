<?php

use config\AllConfig;

define('CLEAR_CACHE_ARG_MASK', 2);
define('CLEAR_CACHE_ARG_ROUTE', 3);

define('OTRA_SUCCESS', CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL);

define('CLEAR_CACHE_MASK_PHP_INTERNAL_CACHE', 1);
define('CLEAR_CACHE_MASK_PHP_BOOTSTRAPS', 2);
define('CLEAR_CACHE_MASK_CSS', 4);
define('CLEAR_CACHE_MASK_JS', 8);
define('CLEAR_CACHE_MASK_TEMPLATES', 16);
define('CLEAR_CACHE_MASK_ROUTE_MANAGEMENT', 32);
define('CLEAR_CACHE_MASK_CLASS_MAPPING', 64);
define('CLEAR_CACHE_MASK_METADATA', 128);
define('CLEAR_CACHE_MASK_DEBUGGING', 256);

$mask = (int) $argv[CLEAR_CACHE_ARG_MASK] ?? 511;
$route = $argv[CLEAR_CACHE_ARG_ROUTE] ?? null;

// Handling route
if (($mask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS) >> 1
  || ($mask & CLEAR_CACHE_MASK_CSS) >> 2
  || ($mask & CLEAR_CACHE_MASK_JS) >> 3
  || ($mask & CLEAR_CACHE_MASK_TEMPLATES) >> 4)
{
  $routes = \config\Routes::$_;

  /**
   * @param string $cachePath
   * @param string $cacheRelativePath
   * @param string $extension
   */
  $removeCachedFiles = function (string $cachePath, string $cacheRelativePath, string $extension) use($routes): void
  {
    checkFolder($cachePath, $cacheRelativePath);

    if (isset($route))
      unlinkFile($cachePath . $route . $extension, $cacheRelativePath . $route . $extension);
    else
    {
      foreach(array_keys($routes) as &$routeToSuppress)
      {
        if (in_array($routeToSuppress, ['otra_exception']))
          continue;

        echo $cachePath . $routeToSuppress . $extension, ' ',
          $cacheRelativePath . $routeToSuppress . $extension, PHP_EOL;
        unlinkFile(
          $cachePath . $routeToSuppress . $extension,
          $cacheRelativePath . $routeToSuppress . $extension
        );
      }
    }
  };

  // If we have chosen a specific route
  if (isset($route))
  {
    // Is this an existing route ? If not ...
    if (!isset($routes[$route]))
    {
      require CONSOLE_PATH . 'tools.php';
      list($newRoute) = guessWords($route, array_keys($routes));

      if ($newRoute === null)
      {
        echo CLI_RED, 'The route ', CLI_YELLOW, $route, CLI_RED, ' does not exist.', END_COLOR;

        return null;
      }

      // Otherwise, we suggest the closest name that we have found.
      $choice = promptUser('There is no route named ' . CLI_WHITE . $route . CLI_YELLOW. ' ! Do you mean ' . CLI_WHITE .
        $newRoute
        . CLI_YELLOW . ' ? (y/n)');

      if ('n' === $choice)
      {
        echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
        return null;
      }

      $route = $newRoute;
    }
  }
}

/**
 * @param string $file
 * @param string $fileShownInTheError
 *
 * @throws \otra\OtraException
 */
function unlinkFile(string $file, string $fileShownInTheError) : void
{
  if (!file_exists($file))
    return;

  if (!unlink($file))
  {
    echo CLI_RED, 'There has been an error during removal of the file ', CLI_CYAN, $fileShownInTheError, CLI_RED,
      '. Task aborted.', END_COLOR, PHP_EOL;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
  }
}

/**
 * @param string $folder
 * @param string $folderShownInTheError
 *
 * @throws \otra\OtraException
 */
function checkFolder(string $folder, string $folderShownInTheError) : void
{
  if (!file_exists($folder))
  {
    echo CLI_YELLOW, 'The folder ', CLI_CYAN, $folderShownInTheError, CLI_YELLOW, ' does not exist. Task aborted.',
      END_COLOR, PHP_EOL;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
  }
}

/* **************** PHP INTERNAL CACHE **************** */
if ($mask & CLEAR_CACHE_MASK_PHP_INTERNAL_CACHE)
{
  if (isset($route))
  {
    $cacheFileName = AllConfig::$cache_path . sha1('ca' . $route . VERSION . 'che');

    // Is there a cache for this route ? If yes, clears it.
    if (file_exists($cacheFileName) === true)
      unlinkFile($cacheFileName, $cacheFileName);

    echo 'The cache for the route ' . $route . ' has been cleared.', PHP_EOL;

    return null;
  }

  // Otherwise we clear all the other routes.
  array_map('unlink', glob(AllConfig::$cache_path . '*.cache'));
  echo 'PHP OTRA internal cache cleared', OTRA_SUCCESS;
}

$phpCachePath = CACHE_PATH . 'php/';
$phpRelativePath = 'cache/php';

// If we want to remove route management, class mapping, metadata and debugging files, we need to check the PHP folder
if ((
    ($mask / CLEAR_CACHE_MASK_ROUTE_MANAGEMENT)
    | ($mask / CLEAR_CACHE_MASK_CLASS_MAPPING)
    | ($mask / CLEAR_CACHE_MASK_METADATA)
    | ($mask / CLEAR_CACHE_MASK_DEBUGGING)
  ) & 1)
  checkFolder($phpCachePath, $phpRelativePath);

/* **************** PHP BOOTSTRAPS **************** */
if (($mask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS) >> 1)
{
  $removeCachedFiles($phpCachePath, $phpRelativePath, '.php');
  echo 'PHP bootstrap(s) cleared', OTRA_SUCCESS;
}

/* **************** CSS **************** */
if (($mask & CLEAR_CACHE_MASK_CSS) >> 2)
{
  $removeCachedFiles(CACHE_PATH . 'css/', 'cache/css', '.gz');
  echo 'CSS files cleared', OTRA_SUCCESS;
}

/* **************** JS **************** */
if (($mask & CLEAR_CACHE_MASK_JS) >> 3)
{
  $removeCachedFiles(CACHE_PATH . 'js/', 'cache/js', '.gz');
  echo 'JS files cleared', OTRA_SUCCESS;
}

/* **************** TEMPLATES **************** */
if (($mask & CLEAR_CACHE_MASK_TEMPLATES) >> 4)
{
  $removeCachedFiles(CACHE_PATH . 'tpl/', 'cache/tpl', '.gz');
  echo 'Templates cleared', OTRA_SUCCESS;
}

/* **************** ROUTE MANAGEMENT **************** */
if (($mask & CLEAR_CACHE_MASK_ROUTE_MANAGEMENT) >> 5)
{
  $routeManagementFile = 'RouteManagement.php';
  unlinkFile(
    $phpCachePath . $routeManagementFile,
    $phpRelativePath . $routeManagementFile
  );

  echo 'Route management file cleared', OTRA_SUCCESS;
}

/* **************** CLASS MAPPING **************** */
if (($mask & CLEAR_CACHE_MASK_CLASS_MAPPING) >> 6)
{
  $classMapFile = 'ClassMap.php';
  unlinkFile(
    $phpCachePath . $classMapFile,
    $phpRelativePath . $classMapFile
  );

  $prodClassMapFile = 'ProdClassMap.php';
  unlinkFile(
    $phpCachePath . $prodClassMapFile,
    $phpRelativePath . $prodClassMapFile
  );

  echo 'Class mapping files cleared', OTRA_SUCCESS;
}

/* **************** CONSOLE TASKS METADATA **************** */
if (($mask & CLEAR_CACHE_MASK_METADATA) >> 7)
{
  $taskClassMapFile = 'tasksClassMap.php';
  unlinkFile(
    $phpCachePath . $taskClassMapFile,
    $phpRelativePath . $taskClassMapFile
  );

  $tasksHelpFile = 'tasksHelp.php';
  unlinkFile(
    $phpCachePath . $tasksHelpFile,
    $phpRelativePath . $tasksHelpFile
  );

  echo 'Metadata cleared', OTRA_SUCCESS;
}

/* **************** DEBUGGING FILES **************** */
if (($mask & CLEAR_CACHE_MASK_DEBUGGING) >> 8)
{
  $profilerFile = 'profiler.php';
  unlinkFile(
    $phpCachePath . $profilerFile,
    $phpRelativePath . $profilerFile
  );

  echo 'Debugging files cleared', OTRA_SUCCESS;
}
?>
