<?php
declare(strict_types=1);

/**
 * This file is used in 'genWatcher' and 'buildDev' tasks to initialize them.
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
namespace otra\console;

use config\AllConfig;
use otra\OtraException;
use function otra\tools\files\returnLegiblePath;

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/cli.php';
require CORE_PATH . 'tools/files/returnLegiblePath.php';

const FILE_TASK_ARG_MASK = 3,
  FILE_TASK_ARG_GCC = 4,
  TASK_FILE_MASK_SCSS = 1,
  TASK_FILE_MASK_TS = 2,
  TASK_FILE_MASK_ROUTES = 4,
  TASK_FILE_MASK_PHP = 8,
  PATHS_TO_HAVE_RESOURCES =
  [
    BASE_PATH . 'bundles',
    BASE_PATH . 'web',
    CORE_PATH
  ],
  RESOURCES_TO_WATCH = ['ts', 'scss', 'sass'];

define('GOOGLE_CLOSURE_COMPILER_VERBOSITY', ['QUIET', 'DEFAULT', 'VERBOSE']);
define(
  'PATHS_TO_AVOID',
  array_merge([BASE_PATH . 'bundles/config'], AllConfig::$pathsToAvoidForBuild ?? [])
);

// Defines if we want to use Google Closure Compiler or not
define(
  'FILE_TASK_GCC',
  isset($argv[FILE_TASK_ARG_GCC]) && $argv[FILE_TASK_ARG_GCC] === 'true'
);

define(
  'TASK_FILE_SOURCE_MAPS',
  isset(AllConfig::$cssSourceMaps) && AllConfig::$cssSourceMaps
);

$maskExists = array_key_exists(FILE_TASK_ARG_MASK, $argv);

// Check if the binary mask is numeric
if ($maskExists && !is_numeric($argv[FILE_TASK_ARG_MASK]))
{
  echo CLI_ERROR, 'The mask must be numeric ! See the help for more information.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

define('FILE_TASK_NUMERIC_MASK', isset($argv[FILE_TASK_ARG_MASK]) ? intval($argv[FILE_TASK_ARG_MASK]) : 15);

define('WATCH_FOR_CSS_RESOURCES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_SCSS));
define('WATCH_FOR_TS_RESOURCES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_TS));
define('WATCH_FOR_PHP_FILES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_PHP));
define(
  'WATCH_FOR_ROUTES',
  (
    $maskExists
    && ($argv[FILE_TASK_ARG_MASK] & TASK_FILE_MASK_ROUTES) === TASK_FILE_MASK_ROUTES
  )
  || !$maskExists
);

unset($maskExists);

/**
 * @param string $baseName               File name without extension nor path
 * @param string $resourcesMainFolder    Full path until the parent folder
 * @param string $resourcesFolderEndPath Full path until 'resources' or 'web' folder
 *                                       Eg : /var/www/html/myProject/bundles/mybundle/myModule/resources/
 * @param string $resourceName           Full path including base file name and extension
 * @param string $extension              File extension
 * @param bool   $verbose
 *
 * @throws OtraException
 * @return string
 */
function generateStylesheetsFiles(
  string $baseName,
  string $resourcesMainFolder,
  string $resourcesFolderEndPath,
  string $resourceName,
  string $extension,
  bool $verbose
) : string
{
  $generatedCssFile = $baseName . '.css';

  // SASS / SCSS (Implemented for Dart SASS as Ruby SASS is deprecated, not tested with LibSass)
  // 5 length of scss/ or sass/
  $cssFolder = $resourcesMainFolder  . 'css/' . substr($resourcesFolderEndPath, 5);

  // if the css folder corresponding to the sass/scss folder does not exist yet, we create it
  // as well as its subfolders
  if (!file_exists($cssFolder))
    mkdir($cssFolder, 0777,true);

  $cssPath = realpath($cssFolder) . '/' . $generatedCssFile;

  // We do not launch an exception on error to avoid stopping the execution of the watcher
  [, $output] = cliCommand(
    'sass ' . (TASK_FILE_SOURCE_MAPS ? '' : '--no-source-map ') . $resourceName . ':' . $cssPath,
    null,
    false
  );

  $sourceMapPath = $cssPath . '.map';

  if ($verbose)
    echo strtoupper($extension) . ' file ', returnLegiblePath($resourceName) . ' have generated ',
      returnLegiblePath($cssPath) .
      (TASK_FILE_SOURCE_MAPS ? ' and ' . returnLegiblePath($sourceMapPath) : ''), '.', PHP_EOL . PHP_EOL;

  // We clean the source map if there is an old source map related to this CSS file
  if (!TASK_FILE_SOURCE_MAPS && file_exists($sourceMapPath))
    unlink($sourceMapPath);

  return $output;
}

/**
 * @param string $fullName The absolute path to the file
 *
 * @throws OtraException
 * @return array{0:string, 1:string, 2:string, 3:string}
 *  $Basename               : the filename without extension,
 *  $resourcesMainFolder    : full path until 'src/resources', 'module/resources' folder or a 'web/' folder,
 *  $resourcesFolderEndPath : last folders in the path after the $resourcesMainFolder
 *  $extension              : ...the file extension
 */
#[\JetBrains\PhpStorm\ArrayShape([
  'string',
  'string',
  'string',
  'string'
])]
function getPathInformations(string $fullName) : array
{
  [$baseName, $extension] = explode('.', basename($fullName));
  $resourceFolder = dirname($fullName);
  $resourcesMainFolderPosition = mb_strrpos($resourceFolder, 'resources');

  // Retrieve the main folder of the resource type whether it is in a 'module/resources' folder or a 'web/' folder
  $folderType = 'resources/';

  if ($resourcesMainFolderPosition === false)
  {
    $resourcesMainFolderPosition = mb_strrpos($resourceFolder, 'web');

    if ($resourcesMainFolderPosition === false)
    {
      echo CLI_ERROR, 'The resource ', CLI_INFO_HIGHLIGHT, $fullName, CLI_ERROR, ' was not in a ', CLI_INFO_HIGHLIGHT,
        'resources', CLI_ERROR, ' or ', CLI_INFO_HIGHLIGHT, 'web', CLI_ERROR, ' folder!', END_COLOR, PHP_EOL;
      debug_print_backtrace();
      throw new OtraException('', 1, '', NULL, [], true);

    }

    $folderType = 'web/';
  }

  $resourcesMainFolder = mb_substr($resourceFolder, 0, $resourcesMainFolderPosition) . $folderType;
  $resourcesFolderEndPath = mb_substr($resourceFolder, mb_strlen($resourcesMainFolder)) . '/';

  return [
    $baseName,
    $resourcesMainFolder,
    $resourcesFolderEndPath,
    $extension
  ];
}

/**
 * @param array  $paths
 * @param string $realPath
 * @param bool   $checkScope Related to the project scope (0: project files, 1: OTRA, 2: All).
 *                           True, if the file belongs to the scope we want to watch. Defaults to true.
 *
 * @return bool
 */
#[Pure] function isNotInThePath(array $paths, string $realPath, bool $checkScope = true) : bool
{
  $continue = true;

  /** @var string $path */
  foreach ($paths as $filePath)
  {
    // If we found a valid base path in the actual path
    if (str_contains($realPath, $filePath) && $checkScope)
      $continue = false;
  }

  return $continue;
}

/**
 * @param int   $fullBinaryMask The binary masks that contains all the options: CSS, TS, JS, CSS etc...
 * @param bool  $maskExists     Does the mask it
 * @param int   $mask
 *
 * @return bool
 */
function isWatched(int $fullBinaryMask, bool $maskExists, int $mask) : bool
{
  return ($maskExists
      && ($fullBinaryMask & $mask) === $mask)
    || !$maskExists;
}
