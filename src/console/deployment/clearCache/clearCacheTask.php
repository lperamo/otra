<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\clearCache;

use otra\config\{AllConfig,Routes};
use otra\OtraException;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{BASE_PATH, CACHE_PATH, CONSOLE_PATH};
use const otra\config\VERSION;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, SUCCESS};
use function otra\console\{guessWords, promptUser};

// arguments
const
  CLEAR_CACHE_ARG_MASK = 2,
  CLEAR_CACHE_ARG_ROUTE = 3,

  // masks
  CLEAR_CACHE_MASK_PHP_INTERNAL_CACHE = 1,
  CLEAR_CACHE_MASK_PHP_BOOTSTRAPS = 2,
  CLEAR_CACHE_MASK_CSS = 4,
  CLEAR_CACHE_MASK_JS = 8,
  CLEAR_CACHE_MASK_TEMPLATES = 16,
  CLEAR_CACHE_MASK_ROUTE_MANAGEMENT = 32,
  CLEAR_CACHE_MASK_CLASS_MAPPING = 64,
  CLEAR_CACHE_MASK_METADATA = 128,
  CLEAR_CACHE_MASK_SECURITY = 256,
  CLEAR_CACHE_MASK_ALL = 511,

  // paths
  PHP_CACHE_PATH = CACHE_PATH . 'php/',
  RELATIVE_PHP_CACHE_PATH = 'cache/php/',
  RELATIVE_PHP_INIT_CACHE_PATH = RELATIVE_PHP_CACHE_PATH . 'init/';

/**
 * @param array $argv
 *
 * @throws OtraException
 * @return void|null
 */
function clearCache(array $argv)
{
  $binaryMask = (int) ($argv[CLEAR_CACHE_ARG_MASK] ?? CLEAR_CACHE_MASK_ALL);

  if ($binaryMask < 1 || $binaryMask > CLEAR_CACHE_MASK_ALL)
  {
    echo CLI_ERROR, 'Wrong mask value of ', CLI_INFO_HIGHLIGHT, $binaryMask, CLI_ERROR . '! It must be between ',
    CLI_INFO_HIGHLIGHT, '1', CLI_ERROR, ' and ', CLI_INFO_HIGHLIGHT, CLEAR_CACHE_MASK_ALL, CLI_ERROR, '.',
    END_COLOR, PHP_EOL;
    throw new OtraException(code:1, exit: true);
  }

  $route = $argv[CLEAR_CACHE_ARG_ROUTE] ?? null;

  // '_once' Mandatory for tests :(
  require_once BASE_PATH . 'config/AllConfig.php';
  // Handling route
  if (($binaryMask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS) >> 1
    || ($binaryMask & CLEAR_CACHE_MASK_CSS) >> 2
    || ($binaryMask & CLEAR_CACHE_MASK_JS) >> 3
    || ($binaryMask & CLEAR_CACHE_MASK_TEMPLATES) >> 4)
  {
    $routes = Routes::$allRoutes;

    /**
     * @param string $cachePath
     * @param string $cacheRelativePath
     * @param string $extension
     *
     * @throws OtraException
     */
    $removeCachedFiles = function (
      string $cachePath,
      string $cacheRelativePath,
      string $extension
    ) use($routes, $route) : void
    {
      checkFolder($cachePath, $cacheRelativePath);

      if (isset($route))
      {
        unlinkFile($cachePath . $route . $extension, $cacheRelativePath . $route . $extension);
        return;
      }

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
    };

    // If we have chosen a specific route and this is not an existing route ...
    if (isset($route) && !isset($routes[$route]))
    {
      require CONSOLE_PATH . 'tools.php';
      [$newRoute] = guessWords($route, array_keys($routes));

      if ($newRoute === null)
      {
        echo CLI_ERROR, 'The route ', CLI_WARNING, $route, CLI_ERROR, ' does not exist.', END_COLOR;

        return null;
      }

      // Otherwise, we suggest the closest name that we have found.
      $choice = promptUser('There is no route named ' . CLI_BASE . $route . CLI_WARNING. ' ! Do you mean ' .
        CLI_BASE . $newRoute . CLI_WARNING . ' ? (y/n)');

      if ('n' === $choice)
      {
        echo CLI_ERROR, 'Sorry then !', END_COLOR, PHP_EOL;
        return null;
      }

      $route = $newRoute;
    }
  }

  /** @var Closure $removeCachedFiles */

  /**
   * @param string $file
   * @param string $fileShownInTheError
   */
  function unlinkFile(string $file, string $fileShownInTheError) : void
  {
    if (!file_exists($file))
      return;

    if (!unlink($file))
    {
      echo CLI_ERROR, 'There has been an error during removal of the file ', CLI_INFO, $fileShownInTheError, CLI_ERROR,
      '. Task aborted.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
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
      echo CLI_WARNING, 'The folder ', CLI_INFO, $folderShownInTheError, CLI_WARNING, ' does not exist. Task aborted.',
      END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
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

    // Otherwise, we clear all the other routes.
    array_map('unlink', glob(AllConfig::$cachePath . '*.cache'));
    echo 'PHP OTRA internal cache cleared', SUCCESS;
  }

// If we want to remove route management, class mapping, metadata and security files, we need to check the PHP folder
  if ((
      ($binaryMask & CLEAR_CACHE_MASK_ROUTE_MANAGEMENT)
      | ($binaryMask & CLEAR_CACHE_MASK_CLASS_MAPPING)
      | ($binaryMask & CLEAR_CACHE_MASK_METADATA)
      | ($binaryMask & CLEAR_CACHE_MASK_SECURITY)
    ) & 1)
    checkFolder(PHP_CACHE_PATH, RELATIVE_PHP_CACHE_PATH);

  /* **************** PHP BOOTSTRAPS **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_PHP_BOOTSTRAPS)
  {
    $removeCachedFiles(PHP_CACHE_PATH, RELATIVE_PHP_CACHE_PATH, '.php');
    $removeCachedFiles(PHP_CACHE_PATH . 'otraRoutes/', RELATIVE_PHP_CACHE_PATH . 'otraRoutes/', '.php');
    echo 'PHP bootstrap(s) cleared', SUCCESS;
  }

  /* **************** CSS **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_CSS)
  {
    define(__NAMESPACE__ . '\\SASS_TREE_CACHE_PATH', CACHE_PATH . 'css/sassTree.php');

    if (file_exists(SASS_TREE_CACHE_PATH))
      unlink(SASS_TREE_CACHE_PATH);

    echo 'SASS/SCSS cached tree cleared', SUCCESS;

    $removeCachedFiles(CACHE_PATH . 'css/', 'cache/css', '.gz');
    echo 'CSS files cleared', SUCCESS;
  }

  /* **************** JS **************** */
  if (($binaryMask & CLEAR_CACHE_MASK_JS) >> 3)
  {
    $removeCachedFiles(CACHE_PATH . 'js/', 'cache/js', '.gz');
    echo 'JS files cleared', SUCCESS;
  }

  /* **************** TEMPLATES **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_TEMPLATES)
  {
    $removeCachedFiles(CACHE_PATH . 'tpl/', 'cache/tpl', '.gz');
    echo 'Templates cleared', SUCCESS;
  }

  /* **************** ROUTE MANAGEMENT **************** */
  if (($binaryMask & CLEAR_CACHE_MASK_ROUTE_MANAGEMENT) >> 5)
  {
    $routeManagementFile = 'RouteManagement.php';
    unlinkFile(
      CACHE_PHP_INIT_PATH . $routeManagementFile,
      RELATIVE_PHP_INIT_CACHE_PATH . $routeManagementFile
    );

    echo 'Route management file cleared', SUCCESS;
  }

  /* **************** CLASS MAPPING **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_CLASS_MAPPING)
  {
    $classMapFile = 'ClassMap.php';
    unlinkFile(
      CACHE_PHP_INIT_PATH . $classMapFile,
      RELATIVE_PHP_INIT_CACHE_PATH . $classMapFile
    );

    $prodClassMapFile = 'ProdClassMap.php';
    unlinkFile(
      CACHE_PHP_INIT_PATH . $prodClassMapFile,
      RELATIVE_PHP_INIT_CACHE_PATH . $prodClassMapFile
    );

    echo 'Class mapping files cleared', SUCCESS;
  }

  /* **************** CONSOLE TASKS METADATA **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_METADATA)
  {
    $taskClassMapFile = 'tasksClassMap.php';
    unlinkFile(
      CACHE_PHP_INIT_PATH . $taskClassMapFile,
      RELATIVE_PHP_INIT_CACHE_PATH . $taskClassMapFile
    );

    $tasksHelpFile = 'tasksHelp.php';
    unlinkFile(
      CACHE_PHP_INIT_PATH . $tasksHelpFile,
      RELATIVE_PHP_INIT_CACHE_PATH . $tasksHelpFile
    );

    echo 'Metadata cleared', SUCCESS;
  }

  /* **************** SECURITY FILES **************** */
  if ($binaryMask & CLEAR_CACHE_MASK_SECURITY)
  {
    $arrayToUnlink = array_merge(
      glob(PHP_CACHE_PATH . 'security/dev/*.php'),
      glob(PHP_CACHE_PATH . '/security/prod/*.php')
    );

    $arraysToUnlink = array_values($arrayToUnlink);

    foreach($arraysToUnlink as $arrayToUnlink)
    {
      unlink($arrayToUnlink);
    }

    unset($arrayToUnlink);
    echo 'Security files cleared', SUCCESS;
  }
}
