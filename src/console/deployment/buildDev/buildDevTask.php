<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\buildDev;

use FilesystemIterator;
use JsonException;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\{BASE_PATH,CONSOLE_PATH,CORE_PATH};
use const otra\console\{CLI_BASE, CLI_WARNING, END_COLOR, SUCCESS};
use const otra\console\deployment\
{
  FILE_TASK_GCC,
  RESOURCES_TO_WATCH,
  WATCH_FOR_CSS_RESOURCES,
  WATCH_FOR_PHP_FILES,
  WATCH_FOR_ROUTES,
  WATCH_FOR_TS_RESOURCES
};
use function otra\console\deployment\
{
  genClassMap\genClassMap,
  generateJavaScript,
  generateStylesheetsFiles,
  getPathInformations,
  updateConf\updateConf
};

const
  BUILD_DEV_ARG_VERBOSE = 2,
  BUILD_DEV_ARG_SCOPE = 5;

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws JsonException|OtraException
 * @return void
 */
function buildDev(array $argumentsVector) : void
{
  require CORE_PATH . 'console/deployment/taskFileInit.php';

  // Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
  define(__NAMESPACE__ . '\\BUILD_DEV_VERBOSE', (int) ($argumentsVector[BUILD_DEV_ARG_VERBOSE] ?? 0));
  define(__NAMESPACE__ . '\\BUILD_DEV_SCOPE', (int) ($argumentsVector[BUILD_DEV_ARG_SCOPE] ?? 0));

  $filesProcessed = false;

  // Handle PHP files
  if (WATCH_FOR_PHP_FILES)
  {
    // We generate the class mapping...
    // "_once ..." needed to avoid a repeatable function definition check
    require_once CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
    genClassMap([]);
    $filesProcessed = true;
  }

  if (WATCH_FOR_ROUTES)
  {
    // We update routes configuration if the PHP file is a routes' configuration file
    echo 'Launching routes update...', PHP_EOL;
    require_once CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
    updateConf('2');
    $filesProcessed = true;
  }

  require CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';
  require CONSOLE_PATH . 'deployment/buildDev/DirectoryFilter.php';
  $dir_iterator = new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS);

  // Convert RESOURCES_TO_WATCH into an associative array for faster lookup
  // SELF_FIRST to have file AND folders to detect an addition of new files
  $iterator = new RecursiveIteratorIterator(
    new DirectoryFilter($dir_iterator, array_flip(RESOURCES_TO_WATCH)),
    RecursiveIteratorIterator::SELF_FIRST
  );

  if (WATCH_FOR_CSS_RESOURCES || WATCH_FOR_TS_RESOURCES)
  {
    /** @var SplFileInfo $entry */
    foreach($iterator as $entry)
    {
      if ($entry->isDir())
        continue;

      // Adding watches for resource files if needed
      $filesProcessed = true;
      $extension = $entry->getExtension();
      $resourceName = $entry->getPathname();
      [$baseName, $resourcesMainFolder, $resourcesFolderEndPath] = getPathInformations($resourceName);

      if ($extension === 'ts' && WATCH_FOR_TS_RESOURCES)
      {
        // 6 = length of devJs/
        $resourcesMainFolder = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 6);
        generateJavaScript(
          false,
          BUILD_DEV_VERBOSE,
          FILE_TASK_GCC,
          $resourcesMainFolder,
          $baseName,
          $resourceName
        );
      }
      elseif ($extension !== 'ts' && !str_starts_with($baseName, '_') && WATCH_FOR_CSS_RESOURCES)
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
      echo CLI_BASE, 'Files have been generated', SUCCESS;
  }
  else
    echo CLI_WARNING, 'No files to process.', END_COLOR, PHP_EOL;
}
