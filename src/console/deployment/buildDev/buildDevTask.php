<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
namespace otra\console;

use RecursiveIteratorIterator;

require CORE_PATH . 'console/deployment/taskFileInit.php';
const BUILD_DEV_ARG_VERBOSE = 2,
BUILD_DEV_ARG_SCOPE = 5;

// Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
define('BUILD_DEV_VERBOSE', (int) ($argv[BUILD_DEV_ARG_VERBOSE] ?? 0));
define('BUILD_DEV_SCOPE', (int) ($argv[BUILD_DEV_ARG_SCOPE] ?? 0));

echo CLI_YELLOW, 'The production configuration is used for this task.', END_COLOR, PHP_EOL;

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

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $extension = $entry->getExtension();

  if (!in_array($extension, RESOURCES_TO_WATCH) || $entry->isDir())
    continue;

  $realPath = $entry->getRealPath();

  if (mb_strpos($realPath, PATH_TO_AVOID) !== false)
    continue;

  // Adding watches for resources files if needed
  if (WATCH_FOR_CSS_RESOURCES || WATCH_FOR_TS_RESOURCES)
  {
    // Does the resources path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(
      PATHS_TO_HAVE_RESOURCES,
      $realPath,
      (BUILD_DEV_SCOPE === 0 && mb_strpos($realPath, CORE_PATH) === false
      || BUILD_DEV_SCOPE === 1 && mb_strpos($realPath, CORE_PATH) !== false
      || BUILD_DEV_SCOPE === 2)
    ))
      continue;

    $filesProcessed = true;
    $resourceName = $entry->getPathname();
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
    echo CLI_GREEN, 'Files have been generated.', END_COLOR, PHP_EOL;
} else
  echo CLI_YELLOW, 'No files to process.', END_COLOR, PHP_EOL;
