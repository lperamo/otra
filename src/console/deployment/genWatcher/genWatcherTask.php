<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genWatcher;

use FilesystemIterator;
use JetBrains\PhpStorm\Pure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\{ADD_BOLD, CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, REMOVE_BOLD_INTENSITY};
use const otra\console\deployment\
{
  FILE_TASK_GCC,
  PATHS_TO_AVOID,
  PATHS_TO_HAVE_RESOURCES,
  RESOURCES_TO_WATCH,
  WATCH_FOR_CSS_RESOURCES,
  WATCH_FOR_PHP_FILES,
  WATCH_FOR_TS_RESOURCES
};
use function otra\console\deployment\{generateJavaScript,generateStylesheetsFiles,getPathInformations,isNotInThePath};
use function otra\tools\files\returnLegiblePath;

// Initialization
require CORE_PATH . 'console/deployment/taskFileInit.php';

const GEN_WATCHER_ARG_VERBOSE = 2,
 EXTENSIONS_TO_WATCH = ['php', 'ts', 'scss', 'sass'],

  PATHS_TO_HAVE_PHP =
  [
    BASE_PATH . 'bundles',
    BASE_PATH . 'config',
    CORE_PATH
  ],

  // Those variables have the same name for folders so we rename those for more clarity (PHP 7.3 at time of writing)
  IN_CLOSE_NOWRITE_DIR = 1073741840,
  IN_OPEN_DIR = 1073741856,
  IN_CREATE_DIR = 1073742080,
  IN_DELETE_DIR = 1073742336;

// Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
define('otra\\console\\deployment\\genWatcher\\GEN_WATCHER_VERBOSE', (int) ($argv[GEN_WATCHER_ARG_VERBOSE] ?? 1));

if (GEN_WATCHER_VERBOSE > 1 )
{
  // Those constants are used in the maximum verbose mode only when we show the main events triggered
  define('otra\\console\\deployment\\genWatcher\\WD_CONSTANTS', [
    IN_ACCESS => 'IN_ACCESS',
    IN_MODIFY => 'IN_MODIFY',
    IN_ATTRIB => 'IN_ATTRIB',
    IN_CLOSE_WRITE => 'IN_CLOSE_WRITE',
    IN_CLOSE_NOWRITE => 'IN_CLOSE_NOWRITE',
    IN_OPEN => 'IN_OPEN',
    IN_MOVED_TO => 'IN_MOVED_TO',
    IN_MOVED_FROM => 'IN_MOVED_FROM',
    IN_CREATE => 'IN_CREATE',
    IN_DELETE => 'IN_DELETE',
    IN_DELETE_SELF => 'IN_DELETE_SELF',
    IN_MOVE_SELF => 'IN_MOVE_SELF',
    IN_CLOSE => 'IN_CLOSE',
    IN_MOVE => 'IN_MOVE',
    IN_ALL_EVENTS => 'IN_ALL_EVENTS',
    IN_UNMOUNT => 'IN_UNMOUNT',
    IN_Q_OVERFLOW => 'IN_Q_OVERFLOW',
    IN_IGNORED => 'IN_IGNORED',
    IN_ISDIR => 'IN_ISDIR',
    IN_CLOSE_NOWRITE_DIR => 'IN_CLOSE_NOWRITE_DIR',
    IN_OPEN_DIR => 'IN_OPEN_DIR',
    IN_CREATE_DIR => 'IN_CREATE_DIR',
    IN_DELETE_DIR => 'IN_DELETE_DIR',
    IN_ONLYDIR => 'IN_ONLYDIR',
    IN_DONT_FOLLOW => 'IN_DONT_FOLLOW',
    IN_MASK_ADD => 'IN_MASK_ADD',
    IN_ONESHOT => 'IN_ONESHOT'
  ]);

  define('otra\\console\\deployment\\genWatcher\\HEADER_EVENT_PADDING', 18);
  define('otra\\console\\deployment\\genWatcher\\HEADER_COOKIE_PADDING', 7);
  define('otra\\console\\deployment\\genWatcher\\HEADER_NAME_PADDING', 30);
  define('otra\\console\\deployment\\genWatcher\\HEADER_WATCHED_RESOURCE_PADDING', 60);

  define('otra\\console\\deployment\\genWatcher\\DATA_EVENT_PADDING', 22);
  define('otra\\console\\deployment\\genWatcher\\DATA_COOKIE_PADDING', 11);
  define('otra\\console\\deployment\\genWatcher\\DATA_NAME_PADDING', 34);
  define('otra\\console\\deployment\\genWatcher\\DATA_WATCHED_RESOURCE_PADDING', 64);
}

/**
 * @param string $header
 * @param int    $padding
 *
 * @return string
 */
#[Pure] function debugHeader(string $header, int $padding) : string
{
  return '│ ' . ADD_BOLD . CLI_BASE . REMOVE_BOLD_INTENSITY . str_pad($header, $padding) .  END_COLOR;
}

/**
 * @param int    $binaryMask
 * @param int    $cookie
 * @param string $filename     Folder or file name
 * @param string $resource Folder of file watched
 * @param bool   $mustShowHeaders  Do we have to show the headers
 *
 * @return string The debug output
 */
#[Pure] function debugEvent(
  int $binaryMask,
  int $cookie,
  string $filename,
  string $resource,
  bool $mustShowHeaders = false
) : string
{
  $debugToPrint = '';

  if ($mustShowHeaders)
    // Headers
    $debugToPrint .= debugHeader('Event',HEADER_EVENT_PADDING)
       . debugHeader('Cookie',HEADER_COOKIE_PADDING)
       . debugHeader('Name',HEADER_NAME_PADDING)
       . debugHeader('Watched resource',HEADER_WATCHED_RESOURCE_PADDING)
       . END_COLOR . PHP_EOL;

  // Data
  $debugToPrint .= CLI_GRAY . str_pad('│ ' . WD_CONSTANTS[$binaryMask], DATA_EVENT_PADDING)
    . str_pad('│ ' . $cookie, DATA_COOKIE_PADDING)
    . str_pad('│ ' . $filename, DATA_NAME_PADDING)
    . END_COLOR;

  return $debugToPrint . str_pad('│ ' . returnLegiblePath($resource), DATA_WATCHED_RESOURCE_PADDING) .
    PHP_EOL;
}

/**
 * Generates class mapping and updates all the configuration files.
 *
 * @param string $filename
 */
function updatePHP(string $filename) : void
{
  // We generate the class mapping...
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

  // We updates routes configuration if the php file is a routes configuration file
  if ($filename === 'Routes.php')
    require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
}

// Configuring inotify
$inotifyInstance = inotify_init();

// this is needed so inotify_read while operate in non blocking mode
// (we then can do echos when we are listening to events)
stream_set_blocking($inotifyInstance, false);

// ******************** ADDING WATCHES ********************

$resourcesEntriesToWatch = $phpEntriesToWatch = $foldersWatchedIds = [];

$dir_iterator = new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

// SASS/SCSS resources (that have dependencies) that we have to watch
$sassMainResources = [];

/** @var SplFileInfo $entry */
foreach($iterator as $entry)
{
  $isFolder = $entry->isDir();
  $extension = $entry->getExtension();

  if (!in_array($extension, EXTENSIONS_TO_WATCH) && !$isFolder)
    continue;

  $realPath = $entry->getRealPath();
  $haveBeenWatched = false;

  foreach (PATHS_TO_AVOID as $pathToAvoid)
  {
    if (str_contains($realPath, $pathToAvoid))
      continue 2;
  }

  // Adding watches for PHP files if needed
  if (WATCH_FOR_PHP_FILES)
  {
    // Does the PHP path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(PATHS_TO_HAVE_PHP, $realPath))
      continue;

    if ($extension === 'php' || $isFolder)
    {
      $phpEntriesToWatch[] = $realPath;

      if ($isFolder)
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $realPath,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $realPath;

      // We avoid to add a watch multiple times on an entry
      $haveBeenWatched = true;
    }
  }

  // Adding watches for resources files if needed
  if (!$haveBeenWatched && (WATCH_FOR_CSS_RESOURCES || WATCH_FOR_TS_RESOURCES))
  {
    // Does the resources path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(PATHS_TO_HAVE_RESOURCES, $realPath))
      continue;

    if (in_array($extension, RESOURCES_TO_WATCH) || $isFolder)
    {
      $resourcesEntriesToWatch[] = $realPath;

      if ($isFolder)
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $realPath,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $realPath;
      else
      {
        $mainResourceFilename = $entry->getFilename();

        if (substr($mainResourceFilename, 0,1) !== '_')
          $sassMainResources[$mainResourceFilename] = $realPath;
      }
    }
  }
}
unset($dir_iterator, $iterator, $entry, $realPath, $mainResourceFilename);

// ******************** INTRODUCTION TEXT ********************

  echo CLI_INFO, (GEN_WATCHER_VERBOSE > 0
    ? 'BASE_PATH' . ' is equal to ' . CLI_INFO_HIGHLIGHT . BASE_PATH . END_COLOR . PHP_EOL
    : 'Watcher started.' . END_COLOR)
    , PHP_EOL;

require CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';

// ******************** Watching ! ********************
while (true)
{
  $events = inotify_read($inotifyInstance);
  $headers = true;

  if ($events !== false)
  {
    $eventsDebug = '';

    // Loop though the events which occurred
    /** @var array{wd:int,mask:int,cookie:int,name:string} $eventDetails */
    foreach ($events as $eventDetails)
    {
      [
        'wd' => $watchDescriptor,
        'mask' => $binaryMask,
        'cookie' => $cookie,
        'name' => $filename
      ] = $eventDetails;

      if ($binaryMask & IN_OPEN || $binaryMask & IN_MOVED_FROM)
        continue;

      $resourceName = is_dir($foldersWatchedIds[$watchDescriptor])
        ? $foldersWatchedIds[$watchDescriptor] . DIR_SEPARATOR . $filename
        : $foldersWatchedIds[$watchDescriptor];

      // If it is a temporary file, we skip it
      if (str_contains(substr($resourceName, -2), '~'))
        continue;

      // User is adding a folder
      if (($binaryMask & IN_CREATE_DIR) === IN_CREATE_DIR)
      {
        // Adding a watch on the new folder
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $resourceName,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $resourceName;

        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .=  PHP_EOL . 'Creating the folder ' . returnLegiblePath($resourceName) . '. We now watching it.' .
            PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);
        }

        continue;
      } elseif (($binaryMask & IN_DELETE_DIR) === IN_DELETE_DIR)
      {
        // User is deleting a folder
        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'Deleting the folder ' . returnLegiblePath($resourceName) .
            '. We do not watch it anymore.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);
        }

        // A watch has been already deleted by inotify on the old folder, we update our variables accordingly
        unset($foldersWatchedIds[$watchDescriptor]);

        continue;
      } elseif ( // If it is an event IN_CREATE and is a file to watch
        ($binaryMask & IN_CREATE) === IN_CREATE
        && (
          !isNotInThePath(PATHS_TO_HAVE_PHP, $resourceName)
        || !isNotInThePath(PATHS_TO_HAVE_RESOURCES, $resourceName)
        )
      )
      {
        $extension = substr($filename, strrpos($filename, '.') + 1);

        // If this is not a file that we want to watch, we skip it.
        if (!in_array($extension, EXTENSIONS_TO_WATCH))
         continue;

        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $resourceName,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $resourceName;

        if ($extension === '.scss' || $extension === '.sass')
        {
          $resourcesEntriesToWatch[] = $resourceName;
          $sassMainResources[$filename] = $resourceName;
        } elseif ($extension ===  'ts')
          $resourcesEntriesToWatch[] = $resourceName;
        elseif ($extension === 'php')
          $phpEntriesToWatch[] = $resourceName;

        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'We are now watching the file ' . returnLegiblePath($filename) . '.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);
        }
      } elseif ( // If it is an event IN_DELETE and is a file to watch
        ($binaryMask & IN_DELETE) === IN_DELETE
        && (in_array($resourceName, $phpEntriesToWatch)
          || in_array($resourceName, $resourcesEntriesToWatch)
        )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'The file ' .
            returnLegiblePath($foldersWatchedIds[$watchDescriptor], $resourceName) .
            ' has been deleted. We remove related generated files.' . PHP_EOL . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);
        }

        // // We make sure not to watch this file again and we clean up related generated files
        if (str_contains($filename, '.scss'))
        {
          unset($resourcesEntriesToWatch[array_search($resourceName, $resourcesEntriesToWatch)]);

          if (substr($filename, 0,1) !== '_')
          {
            unset($sassMainResources[array_search($resourceName, $sassMainResources)]);
            [
              $baseName,
              $resourcesMainFolder,
              $resourcesFolderEndPath
            ] = getPathInformations($resourceName);

            $cssPath = $resourcesMainFolder  . 'css/' . substr($resourcesFolderEndPath, 5) . $baseName . '.css';
            unlink($cssPath);
            $cssMap = $cssPath . '.map';

            if (file_exists($cssMap))
              unlink($cssMap);
          }
        } elseif (str_contains($filename, '.ts'))
        {
          unset($resourcesEntriesToWatch[array_search($resourceName, $resourcesEntriesToWatch)]);
          [
            $baseName,
            $resourcesMainFolder,
            $resourcesFolderEndPath
          ] = getPathInformations($resourceName);

          $jsPath = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 5) . $baseName . '.js';
          unlink($jsPath);
          $jsMap = $jsPath . '.map';

          if (file_exists($jsMap))
            unlink($jsMap);
        }
        elseif (str_contains($filename, '.php'))
        {
          unset($phpEntriesToWatch[array_search($resourceName, $phpEntriesToWatch)]);
          updatePHP($resourceName);
        }

      } elseif ( // A save operation has been done
        (
          ($binaryMask & IN_ATTRIB) === IN_ATTRIB
          || ($binaryMask & IN_MODIFY) === IN_MODIFY
        )
        && (in_array($resourceName, $phpEntriesToWatch)
          || in_array($resourceName, $resourcesEntriesToWatch)
        )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          echo 'The file ' . returnLegiblePath($foldersWatchedIds[$watchDescriptor], $filename)
            . ' modified! We launch the appropriate tasks.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);
        }

        if (in_array($resourceName, $phpEntriesToWatch))
          updatePHP($resourceName);
        elseif (in_array($resourceName, $resourcesEntriesToWatch))
        {
          [
            $baseName,
            $resourcesMainFolder,
            $resourcesFolderEndPath,
            $extension
          ] = getPathInformations($resourceName);

          if ($extension === 'ts')
          {
            // 6 = length of devJs/
            $resourcesMainFolder = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 6);

            generateJavaScript(
              GEN_WATCHER_VERBOSE,
              FILE_TASK_GCC,
              $resourcesMainFolder,
              $baseName,
              $resourceName
            );
          } elseif (substr($baseName, 0, 1) !== '_') // like resource.scss
          {
            $return = generateStylesheetsFiles(
              $baseName,
              $resourcesMainFolder,
              $resourcesFolderEndPath,
              $resourceName,
              $extension,
              GEN_WATCHER_VERBOSE > 0
            );

            if (GEN_WATCHER_VERBOSE > 0)
              $eventsDebug .= $return;
          } else // like _resource.scss
          {
            $stringToTest = substr($baseName, 1);

            foreach($sassMainResources as $mainResource)
            {
              $fileContent = file_get_contents($mainResource);
              preg_match(
                '@\@(?:import|use)\s(?:\'[^\']{0,}\'\s{0,},\s{0,}){0,}\'(?:[^\']{0,}/){0,1}' . $stringToTest .
                '\'@',
                $fileContent,
                $matches
              );

              // If this file does not contain the modified SASS/SCSS file, we look into other watched main resources
              // files.
              if (empty($matches))
                continue;

              [$baseName, $resourcesMainFolder, $resourcesFolderEndPath, $extension] =
                getPathInformations($mainResource);

              $return = generateStylesheetsFiles(
                $baseName,
                $resourcesMainFolder,
                $resourcesFolderEndPath,
                $resourceName,
                $extension,
                GEN_WATCHER_VERBOSE > 0
              );

                if (GEN_WATCHER_VERBOSE > 0)
                  $eventsDebug .= $return;
            }
          }
        }
      } elseif (GEN_WATCHER_VERBOSE > 1)
        $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);

      $headers = false;
    }

    if (GEN_WATCHER_VERBOSE > 0 && $eventsDebug !== '')
      echo $eventsDebug, PHP_EOL;
  }

  // Avoid watching too much to avoid performance issues
  usleep(100);
}
