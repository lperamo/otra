<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\buildDev;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function otra\console\deployment\
{generateJavaScript, generateStylesheetsFiles, getPathInformations, isNotInThePath};
use const otra\cache\php\{BASE_PATH,CONSOLE_PATH,CORE_PATH};
use const otra\console\{CLI_BASE,CLI_SUCCESS,CLI_WARNING,END_COLOR};
use const otra\console\deployment\
{FILE_TASK_GCC,
  PATHS_TO_AVOID,
  PATHS_TO_HAVE_RESOURCES,
  RESOURCES_TO_WATCH,
  WATCH_FOR_CSS_RESOURCES,
  WATCH_FOR_PHP_FILES,
  WATCH_FOR_ROUTES,
  WATCH_FOR_TS_RESOURCES};

require CORE_PATH . 'console/deployment/taskFileInit.php';
const BUILD_DEV_ARG_VERBOSE = 2,
BUILD_DEV_ARG_SCOPE = 5;

// Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
define(__NAMESPACE__ . '\\BUILD_DEV_VERBOSE', (int) ($argv[BUILD_DEV_ARG_VERBOSE] ?? 0));
define(__NAMESPACE__ . '\\BUILD_DEV_SCOPE', (int) ($argv[BUILD_DEV_ARG_SCOPE] ?? 0));

echo CLI_WARNING, 'The production configuration is used for this task.', END_COLOR, PHP_EOL;

$filesProcessed = false;

// Handle PHP files
if (WATCH_FOR_PHP_FILES)
{
  // We generate the class mapping...
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
  $filesProcessed = true;
}

if (WATCH_FOR_ROUTES)
{
  // We updates routes configuration if the php file is a routes configuration file
  echo 'Launching routes update...', PHP_EOL;
  require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
  $filesProcessed = true;
}

require CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';

$dir_iterator = new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

/** @var SplFileInfo $entry */
foreach($iterator as $entry)
{
  $extension = $entry->getExtension();

  if (!in_array($extension, RESOURCES_TO_WATCH) || $entry->isDir())
    continue;

  $realPath = $entry->getRealPath();

  foreach (PATHS_TO_AVOID as $pathToAvoid)
  {
    if (str_contains($realPath, $pathToAvoid))
      continue 2;
  }

  // Adding watches for resources files if needed
  if (WATCH_FOR_CSS_RESOURCES || WATCH_FOR_TS_RESOURCES)
  {
    // Does the resources path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(
      PATHS_TO_HAVE_RESOURCES,
      $realPath,
      (BUILD_DEV_SCOPE === 0 && !str_contains($realPath, CORE_PATH)
      || BUILD_DEV_SCOPE === 1 && str_contains($realPath, CORE_PATH)
      || BUILD_DEV_SCOPE === 2)
    ))
      continue;

    $filesProcessed = true;
    $resourceName = $entry->getPathname();

    // starters are only meant to be copied, not used
    if (str_contains($resourceName, 'starters'))
      continue;

    [$baseName, $resourcesMainFolder, $resourcesFolderEndPath] = getPathInformations($resourceName);

    if ($extension === 'ts')
    {
      // 6 = length of devJs/
      $resourcesMainFolder = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 6);

      if (WATCH_FOR_TS_RESOURCES)
        generateJavaScript(
          BUILD_DEV_VERBOSE,
          FILE_TASK_GCC,
          $resourcesMainFolder,
          $baseName,
          $resourceName
        );
    } elseif (substr($baseName, 0, 1) !== '_' && WATCH_FOR_CSS_RESOURCES)
      generateStylesheetsFiles(
        $baseName,
        $resourcesMainFolder,
        $resourcesFolderEndPath,
        $resourceName,
        $extension,
        BUILD_DEV_VERBOSE > 0
      );
  }
}

unset($dir_iterator, $iterator, $entry, $realPath);

if ($filesProcessed)
{
  if (BUILD_DEV_VERBOSE === 0)
    echo CLI_BASE, 'Files have been generated', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
} else
  echo CLI_WARNING, 'No files to process.', END_COLOR, PHP_EOL;
