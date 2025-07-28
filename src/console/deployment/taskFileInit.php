<?php
/**
 * This file is used in 'genWatcher' and 'buildDev' tasks to initialize them.
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment;

use otra\config\AllConfig;
use JetBrains\PhpStorm\ArrayShape;
use otra\OtraException;
use const otra\cache\php\
{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tools\runCommandWithEnvironment;
use function otra\tools\files\returnLegiblePath;

if (!file_exists(BUNDLES_PATH . 'config/Routes.php'))
{
  echo CLI_ERROR, 'Either you do not have any routes or you have to update your configuration with ', CLI_INFO_HIGHLIGHT,
    'otra updateConf', CLI_ERROR, '.', PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

require_once BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/cli.php';
require_once CORE_PATH . 'tools/files/returnLegiblePath.php';

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
define(
  __NAMESPACE__ . '\\DEFAULTS_PATH_TO_AVOID',
  array_merge([
    BASE_PATH . '.git',
    BASE_PATH . '.idea', // Jetbrains folder
    BASE_PATH . '.scannerwork', // SonarQube
    BASE_PATH . 'cache',
    BUNDLES_PATH . 'config',
    BASE_PATH . 'logs'
  ],
  OTRA_PROJECT
    ? []
    : [
      BASE_PATH . 'doc',
      BASE_PATH . 'phpdoc',
      BASE_PATH . 'reports',
      BASE_PATH . 'sassdoc',
      BASE_PATH . 'tests/.coverage-cache', // PHPUnit's coverage cache folder
      BASE_PATH . 'tests/config/data',
      BASE_PATH . 'tests/config/serverConfBackup',
      BASE_PATH . 'tests/examples/nginx',
      BASE_PATH . 'vendor'
    ]
  )
);
define(
  __NAMESPACE__ . '\\PATHS_TO_AVOID',
  array_merge(DEFAULTS_PATH_TO_AVOID, AllConfig::$pathsToAvoidForBuild ?? [])
);

// Defines if we want to use Google Closure Compiler or not
define(
  __NAMESPACE__ . '\\FILE_TASK_GCC',
  isset($argumentsVector[FILE_TASK_ARG_GCC]) && $argumentsVector[FILE_TASK_ARG_GCC] === 'true'
);

define(
  __NAMESPACE__ . '\\TASK_FILE_SOURCE_MAPS',
  isset(AllConfig::$cssSourceMaps) && AllConfig::$cssSourceMaps
);

$maskExists = array_key_exists(FILE_TASK_ARG_MASK, $argumentsVector);

// Check if the binary mask is numeric
if ($maskExists && !is_numeric($argumentsVector[FILE_TASK_ARG_MASK]))
{
  echo CLI_ERROR, 'The mask must be numeric ! See the help for more information.', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

define(__NAMESPACE__ . '\\FILE_TASK_NUMERIC_MASK', isset($argumentsVector[FILE_TASK_ARG_MASK]) ? (int) $argumentsVector[FILE_TASK_ARG_MASK] : 15);

define(__NAMESPACE__ . '\\WATCH_FOR_CSS_RESOURCES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_SCSS));
define(__NAMESPACE__ . '\\WATCH_FOR_TS_RESOURCES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_TS));
define(__NAMESPACE__ . '\\WATCH_FOR_PHP_FILES', isWatched(FILE_TASK_NUMERIC_MASK, $maskExists, TASK_FILE_MASK_PHP));
define(
  __NAMESPACE__ . '\\WATCH_FOR_ROUTES',
  (
    $maskExists
    && ($argumentsVector[FILE_TASK_ARG_MASK] & TASK_FILE_MASK_ROUTES) === TASK_FILE_MASK_ROUTES
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
  $cssFolder = $resourcesMainFolder  . 'css/' . mb_substr($resourcesFolderEndPath, 5);

  // if the CSS folder corresponding to the sass/scss folder does not exist yet, we create it
  // as well as its subfolders
  if (!file_exists($cssFolder))
    mkdir($cssFolder, 0777,true);

  $cssPath = realpath($cssFolder) . DIR_SEPARATOR . $generatedCssFile;

  // We do not launch an exception on error to avoid stopping the execution of the watcher
  $sassLoadPathString = '';
  $sassLoadPaths = array_merge([CORE_PATH . 'resources/scss/'], AllConfig::$sassLoadPaths);

  foreach ($sassLoadPaths as $sassLoadPath)
    $sassLoadPathString .= ' -I ' . $sassLoadPath;

  // `--update` is not used as it can hide errors from files not being modified recently.
  [, $output] = runCommandWithEnvironment(
    AllConfig::$nodeBinariesPath .
    'sass' . ' -s compressed ' . (PHP_OS === 'Linux' 
      ? '--fatal-deprecation=$(' . AllConfig::$nodeBinariesPath . 'sass --version | cut -d" " -f1) ' 
      : ''
    ) .  $sassLoadPathString .
    (TASK_FILE_SOURCE_MAPS
      ? ' '
      : ' --no-source-map '
    ) . $resourceName . ':' . $cssPath,
    ['PATH' => getenv('PATH')],
    null,
    false
  );

  $sourceMapPath = $cssPath . '.map';

  if ($verbose)
    echo strtoupper($extension) . ' file ', returnLegiblePath($resourceName) . ' have generated ',
      returnLegiblePath($cssPath) .
      (TASK_FILE_SOURCE_MAPS ? ' and ' . returnLegiblePath($sourceMapPath) : ''), '.', PHP_EOL;

  // We clean the source map if there is an old source map related to this CSS file
  if (!TASK_FILE_SOURCE_MAPS && file_exists($sourceMapPath))
    unlink($sourceMapPath);

  // removes "Compiled [...]" messages from SASS CLI command as OTRA already talks about it
  // also removes dates
  return preg_replace('@\[[^]]+] Compiled .*?.scss to .*?.css\.@','',$output);
}

/**
 * @param string $fullName The absolute path to the file
 *
 * @return array{0:string, 1:string, 2:string, 3:string}
 *  $Basename               : the filename without extension,
 *  $resourcesMainFolder    : full path until 'src/resources', 'module/resources' folder or a 'web/' folder,
 *  $resourcesFolderEndPath : last folders in the path after the $resourcesMainFolder
 *  $extension              : ...the file extension
 */
#[ArrayShape([
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
      $resourcesMainFolderPosition = mb_strrpos($resourceFolder, 'vendor/ecocomposer/ecocomposer');

      if ($resourcesMainFolderPosition === false)
      {
        echo CLI_ERROR, 'The resource ', CLI_INFO_HIGHLIGHT, $fullName, CLI_ERROR, ' was not in a ', CLI_INFO_HIGHLIGHT,
          'resources', CLI_ERROR, ',', CLI_INFO_HIGHLIGHT, 'web', CLI_ERROR, ' or ', CLI_INFO_HIGHLIGHT,
          'vendor/ecocomposer/ecocomposer', CLI_ERROR, ' folder!', END_COLOR, PHP_EOL;
      } else
        $folderType = 'vendor/ecocomposer/ecocomposer/';

    } else
      $folderType = 'web/';
  }

  $resourcesMainFolder = mb_substr($resourceFolder, 0, $resourcesMainFolderPosition) . $folderType;
  $resourcesFolderEndPath = mb_substr($resourceFolder, mb_strlen($resourcesMainFolder)) . DIR_SEPARATOR;

  return [
    $baseName,
    $resourcesMainFolder,
    $resourcesFolderEndPath,
    $extension
  ];
}

/**
 * @param bool   $checkScope Related to the project scope (0: project files, 1: OTRA, 2: All).
 *                           True, if the file belongs to the scope, we want to watch. Defaults to true.
 *
 * @return bool
 */
function isNotInThePath(array $paths, string $realPath, bool $checkScope = true) : bool
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
 * @param int   $fullBinaryMask The binary mask that contains all the options: CSS, TS, JS, CSS, etc...
 * @param bool  $maskExists     Does the mask it
 *
 * @return bool
 */
function isWatched(int $fullBinaryMask, bool $maskExists, int $mask) : bool
{
  return ($maskExists
      && ($fullBinaryMask & $mask) === $mask)
    || !$maskExists;
}
