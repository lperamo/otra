<?php
declare(strict_types=1);

namespace otra\console;

use config\AllConfig;
use JetBrains\PhpStorm\Pure;
use RecursiveIteratorIterator;

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/cli.php';

const BUILD_DEV_ARG_VERBOSE = 2,
BUILD_DEV_ARG_MASK = 3,
BUILD_DEV_ARG_GCC = 4,
BUILD_DEV_ARG_SCOPE = 5,
BUILD_DEV_MASK_SCSS = 1,
BUILD_DEV_MASK_TS = 2,
BUILD_DEV_MASK_ROUTES = 4,
BUILD_DEV_MASK_PHP = 8,
GOOGLE_CLOSURE_COMPILER_VERBOSITY = ['QUIET', 'DEFAULT', 'VERBOSE'],
RESOURCES_TO_WATCH = ['ts', 'scss', 'sass'],

PATHS_TO_HAVE_PHP =
[
  BASE_PATH . 'bundles',
  BASE_PATH . 'config',
  CORE_PATH
],

PATHS_TO_HAVE_RESOURCES =
[
  BASE_PATH . 'bundles',
  BASE_PATH . 'web',
  CORE_PATH
],

PATH_TO_AVOID = BASE_PATH . 'bundles/config';

// Reminder : 0 => no debug, 1 => basic logs
define(
  'BUILD_DEV_VERBOSE',
  (int) ($argv[BUILD_DEV_ARG_VERBOSE] ?? 0)
);

define(
  'BUILD_DEV_GCC',
  isset($argv[BUILD_DEV_ARG_GCC]) && $argv[BUILD_DEV_ARG_GCC] === 'true'
);

define(
  'BUILD_DEV_SCOPE',
  (int) ($argv[BUILD_DEV_ARG_SCOPE] ?? 0)
);

/**
 * @param array  $paths
 * @param string $realPath
 *
 * @return bool
 */
#[Pure] function isNotInThePath(array $paths, string $realPath) : bool
{
  $continue = true;

  foreach ($paths as $path)
  {
    // If we found a valid base path in the actual path
    if (mb_strpos($realPath, $path) !== false &&
        (BUILD_DEV_SCOPE === 0 && mb_strpos($realPath, CORE_PATH) === false
          || BUILD_DEV_SCOPE === 1 && mb_strpos($realPath, CORE_PATH) !== false
          || BUILD_DEV_SCOPE === 2)
      )
        $continue = false;
  }

  return $continue;
}

/**
 * Returns BASE_PATH the/path with BASE_PATH in light blue whether the resource is contained in the BASE_PATH
 * otherwise returns resource name as is.
 *
 * @param string      $resource Most of the time the name of a folder
 * @param string|null $name     Most of the time the name of a file
 * @param bool|null   $endColor Do we have to reset color at the end ?
 *
 * @return string
 */
#[Pure] function returnLegiblePath(string $resource, ?string $name = '', ?bool $endColor = true) : string
{
  // Avoid to finish with '/' if $resource is not a folder (and then $name = '')
  if ($name !== '')
    $name = '/' . $name;

  return (str_contains($resource, BASE_PATH)
      ? CLI_LIGHT_BLUE . 'BASE_PATH ' . CLI_LIGHT_CYAN . substr($resource, strlen(BASE_PATH)) . $name . END_COLOR
      : CLI_LIGHT_CYAN . $resource . $name . END_COLOR)
    . ($endColor ? END_COLOR : '');
}

/**
 * @param array $argv       Command line arguments
 * @param bool  $maskExists
 * @param int   $genWatcherMask
 *
 * @return bool
 */
$isWatched = function (array $argv, bool $maskExists, int $genWatcherMask) : bool
{
  return (
      $maskExists === true
      && ($argv[BUILD_DEV_ARG_MASK] & $genWatcherMask) === $genWatcherMask
    )
    || $maskExists === false;
};

$maskExists = array_key_exists(BUILD_DEV_ARG_MASK, $argv);

// Check if the binary mask is numeric
if ($maskExists === true && is_numeric($argv[BUILD_DEV_ARG_MASK]) === false)
{
  echo CLI_RED, 'The mask must be numeric ! See the help for more information.', END_COLOR, PHP_EOL;
  exit(1);
}

echo CLI_YELLOW, 'The production configuration is used for this task.', END_COLOR, PHP_EOL;

define(
  'BUILD_DEV_SOURCE_MAPS',
  isset(AllConfig::$cssSourceMaps) && AllConfig::$cssSourceMaps
);

define('WATCH_FOR_CSS_RESOURCES', $isWatched($argv, $maskExists, BUILD_DEV_MASK_SCSS));
define('WATCH_FOR_TS_RESOURCES', $isWatched($argv, $maskExists, BUILD_DEV_MASK_TS));
define('WATCH_FOR_PHP_FILES', $isWatched($argv, $maskExists, BUILD_DEV_MASK_PHP));

unset($isWatched);

define(
  'WATCH_FOR_ROUTES',
  (
    $maskExists === true
    && ($argv[BUILD_DEV_ARG_MASK] & BUILD_DEV_MASK_ROUTES) === BUILD_DEV_MASK_ROUTES
  )
  || $maskExists === false
);

unset($maskExists);

$filesProcessed = false;

// Handle PHP files
if (WATCH_FOR_PHP_FILES === true)
{
  // We generate the class mapping...
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
  $filesProcessed = true;
}

if (WATCH_FOR_ROUTES === true)
{
  // We updates routes configuration if the php file is a routes configuration file
  echo 'Launching routes update...', PHP_EOL;
  require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
  $filesProcessed = true;
}

require CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $extension = $entry->getExtension();

  if (in_array($extension, RESOURCES_TO_WATCH) === false || $entry->isDir() === true)
    continue;

  $realPath = $entry->getRealPath();

  if (mb_strpos($realPath, PATH_TO_AVOID) !== false)
    continue;

  // Adding watches for resources files if needed
  if (WATCH_FOR_CSS_RESOURCES === true || WATCH_FOR_TS_RESOURCES === true)
  {
    // Does the resources path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(PATHS_TO_HAVE_RESOURCES, $realPath) === true)
      continue;

    $filesProcessed = true;

    $extension = $entry->getExtension();
    $baseName = substr($entry->getFilename(), 0, -strlen($extension) - 1);
    $resourceName = $entry->getPathname();
    $resourceFolder = dirname($resourceName);

    $resourcesMainFolderPosition = mb_strrpos($resourceFolder, 'resources');

    // Retrieve the main folder of the resource type whether it is in a 'module/resources' folder or a 'web/' folder
    $resourcesMainFolder =
      $resourcesMainFolderPosition !== false
        ? substr(
          $resourceFolder,
          0,
          $resourcesMainFolderPosition
        ) . 'resources/'
        : substr(
          $resourceFolder,
          0,
          mb_strrpos($resourceFolder, 'web')
        ) . 'web/';

    $resourcesFolderEndPath = mb_substr($resourceFolder, mb_strlen($resourcesMainFolder)) . '/';

    if ($extension === 'ts')
    {
      // 6 = length of devJs/
      $resourcesMainFolder = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 6);

      if (WATCH_FOR_TS_RESOURCES === true)
        generateJavaScript(
          BUILD_DEV_VERBOSE,
          BUILD_DEV_GCC,
          $resourcesMainFolder,
          $baseName,
          $resourceName
        );
    }
    elseif (substr($baseName, 0, 1) !== '_')
    {
      if (WATCH_FOR_CSS_RESOURCES === true)
      {
        $generatedCssFile = $baseName . '.css';

        // SASS / SCSS (Implemented for Dart SASS as Ruby SASS is deprecated, not tested with LibSass)
        // 5 length of scss/ or sass/
        $cssFolder = $resourcesMainFolder  . 'css/' . substr($resourcesFolderEndPath, 5);

        // if the css folder corresponding to the sass/scss folder does not exist yet, we create it
        // as well as its subfolders
        if (file_exists($cssFolder) === false)
          mkdir($cssFolder, 0777,true);

        $cssPath = realpath($cssFolder) . '/' . $generatedCssFile;

        cli('sass ' . (BUILD_DEV_SOURCE_MAPS ? '' : '--no-source-map ') . $resourceName . ':' . $cssPath);

        $sourceMapPath = $cssPath . '.map';

        if (BUILD_DEV_VERBOSE > 0)
          echo strtoupper($extension) . ' file ', returnLegiblePath($resourceName) . ' have generated ',
            returnLegiblePath($cssPath) .
            (BUILD_DEV_SOURCE_MAPS ? ' and ' . returnLegiblePath($sourceMapPath) : ''), '.', PHP_EOL . PHP_EOL;

        // We clean the source map if there is an old source map related to this CSS file
        if (!BUILD_DEV_SOURCE_MAPS && file_exists($sourceMapPath))
            unlink($sourceMapPath);
      }
    }
  }
}

unset($dir_iterator, $iterator, $entry, $realPath);

if ($filesProcessed === true)
{
  if (BUILD_DEV_VERBOSE === 0)
    echo CLI_GREEN, 'Files have been generated.', END_COLOR, PHP_EOL;
} else
  echo CLI_YELLOW, 'No files to process.', END_COLOR, PHP_EOL;

