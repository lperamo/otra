<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use config\AllConfig;
use otra\OtraException;

// arguments
define('CLEAR_CACHE_ARG_MASK', 2);
define('CLEAR_CACHE_ARG_ROUTE', 3);

// masks
define('CLEAR_CACHE_MASK_PHP_INTERNAL_CACHE', 1);
define('CLEAR_CACHE_MASK_PHP_BOOTSTRAPS', 2);
define('CLEAR_CACHE_MASK_CSS', 4);
define('CLEAR_CACHE_MASK_JS', 8);
define('CLEAR_CACHE_MASK_TEMPLATES', 16);
define('CLEAR_CACHE_MASK_ROUTE_MANAGEMENT', 32);
define('CLEAR_CACHE_MASK_CLASS_MAPPING', 64);
define('CLEAR_CACHE_MASK_METADATA', 128);
define('CLEAR_CACHE_MASK_SECURITY', 256);

// formatting
define('OTRA_SUCCESS', CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL);

// paths
define('PHP_CACHE_PATH', CACHE_PATH . 'php/');
define('RELATIVE_PHP_CACHE_PATH', 'cache/php');

$binaryMask = (int) ($argv[CLEAR_CACHE_ARG_MASK] ?? 511);
$route = $argv[CLEAR_CACHE_ARG_ROUTE] ?? null;

// Handling route
if (($binaryMask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS) >> 1
  || ($binaryMask & CLEAR_CACHE_MASK_CSS) >> 2
  || ($binaryMask & CLEAR_CACHE_MASK_JS) >> 3
  || ($binaryMask & CLEAR_CACHE_MASK_TEMPLATES) >> 4)
{
  $routes = \config\Routes::$allRoutes;

  /**
   * @param string $cachePath
   * @param string $cacheRelativePath
   * @param string $extension
   */
  $removeCachedFiles = function (
    string $cachePath,
    string $cacheRelativePath,
    string $extension
  ) use($routes, $route) : void
  {
    checkFolder($cachePath, $cacheRelativePath);

    if (isset($route))
      unlinkFile($cachePath . $route . $extension, $cacheRelativePath . $route . $extension);
    else
    {
      foreach(array_keys($routes) as $routeToSuppress)
      {
        if ($routeToSuppress === 'otra_exception')
          continue;

        $routeFileName = $routeToSuppress . $extension;

        if ($extension !== '.php')
          $routeFileName = sha1('ca' . $routeToSuppress . VERSION . 'che') . $extension;

        unlinkFile(
          $cachePath . $routeFileName,
          $cacheRelativePath . $routeFileName
        );
      }
    }
  };

  // If we have chosen a specific route and this is not an existing route ...
  if (isset($route) && !isset($routes[$route]))
  {
    require CONSOLE_PATH . 'tools.php';
    list($newRoute) = guessWords($route, array_keys($routes));

    if ($newRoute === null)
    {
      echo CLI_RED, 'The route ', CLI_YELLOW, $route, CLI_RED, ' does not exist.', END_COLOR;

      return null;
    }

    // Otherwise, we suggest the closest name that we have found.
    $choice = promptUser('There is no route named ' . CLI_WHITE . $route . CLI_YELLOW. ' ! Do you mean ' .
      CLI_WHITE . $newRoute . CLI_YELLOW . ' ? (y/n)');

    if ('n' === $choice)
    {
      echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
      return null;
    }

    $route = $newRoute;
  }
}

/** @var Closure $removeCachedFiles */

/**
 * @param string $file
 * @param string $fileShownInTheError
 *
 * @throws OtraException
 */
function unlinkFile(string $file, string $fileShownInTheError) : void
{
  if (!file_exists($file))
    return;

  if (!unlink($file))
  {
    echo CLI_RED, 'There has been an error during removal of the file ', CLI_CYAN, $fileShownInTheError, CLI_RED,
      '. Task aborted.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

/**
 * @param string $folder
 * @param string $folderShownInTheError
 *
 * @throws OtraException
 */
function checkFolder(string $folder, string $folderShownInTheError) : void
{
  if (!file_exists($folder))
  {
    echo CLI_YELLOW, 'The folder ', CLI_CYAN, $folderShownInTheError, CLI_YELLOW, ' does not exist. Task aborted.',
      END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

/* **************** PHP INTERNAL CACHE **************** */
if ($binaryMask & CLEAR_CACHE_MASK_PHP_INTERNAL_CACHE)
{
  if (isset($route))
  {
    $cacheFileName = AllConfig::$cachePath . sha1('ca' . $route . VERSION . 'che');

    // Is there a cache for this route ? If yes, clears it.
    if (file_exists($cacheFileName) === true)
      unlinkFile($cacheFileName, $cacheFileName);

    echo 'The cache for the route ' . $route . ' has been cleared.', PHP_EOL;

    return null;
  }

  // Otherwise we clear all the other routes.
  array_map('unlink', glob(AllConfig::$cachePath . '*.cache'));
  echo 'PHP OTRA internal cache cleared', OTRA_SUCCESS;
}

// If we want to remove route management, class mapping, metadata and security files, we need to check the PHP folder
if ((
    ($binaryMask / CLEAR_CACHE_MASK_ROUTE_MANAGEMENT)
    | ($binaryMask / CLEAR_CACHE_MASK_CLASS_MAPPING)
    | ($binaryMask / CLEAR_CACHE_MASK_METADATA)
    | ($binaryMask / CLEAR_CACHE_MASK_SECURITY)
  ) & 1)
  checkFolder(PHP_CACHE_PATH, RELATIVE_PHP_CACHE_PATH);

/* **************** PHP BOOTSTRAPS **************** */
if (($binaryMask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS) >> 1)
{
  $removeCachedFiles(PHP_CACHE_PATH, RELATIVE_PHP_CACHE_PATH, '.php');
  echo 'PHP bootstrap(s) cleared', OTRA_SUCCESS;
}

/* **************** CSS **************** */
if (($binaryMask & CLEAR_CACHE_MASK_CSS) >> 2)
{
  $removeCachedFiles(CACHE_PATH . 'css/', 'cache/css', '.gz');
  echo 'CSS files cleared', OTRA_SUCCESS;
}

/* **************** JS **************** */
if (($binaryMask & CLEAR_CACHE_MASK_JS) >> 3)
{
  $removeCachedFiles(CACHE_PATH . 'js/', 'cache/js', '.gz');
  echo 'JS files cleared', OTRA_SUCCESS;
}

/* **************** TEMPLATES **************** */
if (($binaryMask & CLEAR_CACHE_MASK_TEMPLATES) >> 4)
{
  $removeCachedFiles(CACHE_PATH . 'tpl/', 'cache/tpl', '.gz');
  echo 'Templates cleared', OTRA_SUCCESS;
}

/* **************** ROUTE MANAGEMENT **************** */
if (($binaryMask & CLEAR_CACHE_MASK_ROUTE_MANAGEMENT) >> 5)
{
  $routeManagementFile = 'RouteManagement.php';
  unlinkFile(
    PHP_CACHE_PATH . 'init/' . $routeManagementFile,
    RELATIVE_PHP_CACHE_PATH . 'init/' . $routeManagementFile
  );

  echo 'Route management file cleared', OTRA_SUCCESS;
}

/* **************** CLASS MAPPING **************** */
if (($binaryMask & CLEAR_CACHE_MASK_CLASS_MAPPING) >> 6)
{
  $classMapFile = 'ClassMap.php';
  unlinkFile(
    PHP_CACHE_PATH . 'init/' . $classMapFile,
    RELATIVE_PHP_CACHE_PATH . 'init/' . $classMapFile
  );

  $prodClassMapFile = 'ProdClassMap.php';
  unlinkFile(
    PHP_CACHE_PATH . 'init/' . $prodClassMapFile,
    RELATIVE_PHP_CACHE_PATH . 'init/' . $prodClassMapFile
  );

  echo 'Class mapping files cleared', OTRA_SUCCESS;
}

/* **************** CONSOLE TASKS METADATA **************** */
if (($binaryMask & CLEAR_CACHE_MASK_METADATA) >> 7)
{
  $taskClassMapFile = 'tasksClassMap.php';
  unlinkFile(
    PHP_CACHE_PATH . 'init/' . $taskClassMapFile,
    RELATIVE_PHP_CACHE_PATH . 'init/' . $taskClassMapFile
  );

  $tasksHelpFile = 'tasksHelp.php';
  unlinkFile(
    PHP_CACHE_PATH . 'init/' . $tasksHelpFile,
    RELATIVE_PHP_CACHE_PATH . 'init/' . $tasksHelpFile
  );

  echo 'Metadata cleared', OTRA_SUCCESS;
}

/* **************** SECURITY FILES **************** */
if (($binaryMask & CLEAR_CACHE_MASK_SECURITY) >> 8)
{
  $arrayToUnlink = array_merge(
    glob(PHP_CACHE_PATH . 'security/dev/*.php'),
    glob(PHP_CACHE_PATH . '/security/prod/*.php')
  );

  array_walk(
    $arrayToUnlink,
    'unlink'
  );
  unset($arrayToUnlink);

  echo 'Security files cleared', OTRA_SUCCESS;
}

