<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
namespace otra\console;

use JetBrains\PhpStorm\Pure;
use otra\console\Tasks;
use RecursiveIteratorIterator;

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
define('GEN_WATCHER_VERBOSE', (int) ($argv[GEN_WATCHER_ARG_VERBOSE] ?? 1));

if (GEN_WATCHER_VERBOSE > 1 )
{
  // Those constants are used in the maximum verbose mode only when we show the main events triggered
  define('WD_CONSTANTS', [
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

  define('HEADER_EVENT_PADDING', 18);
  define('HEADER_COOKIE_PADDING', 7);
  define('HEADER_NAME_PADDING', 30);
  define('HEADER_WATCHED_RESOURCE_PADDING', 60);

  define('DATA_EVENT_PADDING', 22);
  define('DATA_COOKIE_PADDING', 11);
  define('DATA_NAME_PADDING', 34);
  define('DATA_WATCHED_RESOURCE_PADDING', 64);
}

/**
 * @param string $header
 * @param int    $padding
 *
 * @return string
 */
#[Pure] function debugHeader(string $header, int $padding) : string
{
  return '│ ' . CLI_BOLD_WHITE . str_pad($header, $padding) .  END_COLOR;
}

/**
 * @param int    $mask
 * @param int    $cookie
 * @param string $name     Folder or file name
 * @param string $resource Folder of file watched
 * @param bool   $mustShowHeaders  Do we have to show the headers
 *
 * @return string The debug output
 */
#[Pure] function debugEvent(
  int $mask,
  int $cookie,
  string $name,
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
  $debugToPrint .= CLI_LIGHT_GRAY . str_pad('│ ' . WD_CONSTANTS[$mask], DATA_EVENT_PADDING)
    . str_pad('│ ' . $cookie, DATA_COOKIE_PADDING)
    . str_pad('│ ' . $name, DATA_NAME_PADDING)
    . END_COLOR;

  return $debugToPrint . str_pad('│ ' . returnLegiblePath($resource), DATA_WATCHED_RESOURCE_PADDING) .
    PHP_EOL;
}

// Configuring inotify
$inotifyInstance = inotify_init();

// this is needed so inotify_read while operate in non blocking mode
// (we then can do echos when we are listening to events)
stream_set_blocking($inotifyInstance, false);

// ******************** ADDING WATCHES ********************

$resourcesEntriesToWatch = $phpEntriesToWatch = $foldersWatchedIds = [];

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

// SASS/SCSS resources (that have dependencies) that we have to watch
$sassMainResources = [];

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $isFolder = $entry->isDir();
  $extension = $entry->getExtension();

  if (!in_array($extension, EXTENSIONS_TO_WATCH) && !$isFolder)
    continue;

  $realPath = $entry->getRealPath();
  $haveBeenWatched = false;

  if (mb_strpos($realPath, PATH_TO_AVOID) !== false)
    continue;

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

  echo CLI_LIGHT_BLUE, (GEN_WATCHER_VERBOSE > 0
    ? 'BASE_PATH' . ' is equal to ' . CLI_LIGHT_CYAN . BASE_PATH . END_COLOR . PHP_EOL
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
    /** @var array $eventDetails */
    foreach ($events as $eventDetails)
    {
      /**
       * @var int    $wd
       * @var int    $mask
       * @var int    $cookie
       * @var string $name
       */
      extract($eventDetails);

      if ($mask & IN_OPEN || $mask & IN_MOVED_FROM)
        continue;

      $resourceName = $foldersWatchedIds[$wd] . '/' . $name;

      // User is adding a folder
      if (($mask & IN_CREATE_DIR) === IN_CREATE_DIR)
      {
        $folderPath = $foldersWatchedIds[$wd] . '/' . $name;

        // Adding a watch on the new folder
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $folderPath,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $folderPath;

        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .=  PHP_EOL . 'Creating the folder ' . returnLegiblePath($folderPath) . '. We now watching it.' .
            PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        continue;
      } elseif (($mask & IN_DELETE_DIR) === IN_DELETE_DIR)
      {
        // User is deleting a folder
        $folderPath = $foldersWatchedIds[$wd] . '/' . $name;

        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'Deleting the folder ' . returnLegiblePath($folderPath) .
            '. We do not watch it anymore.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        // A watch has been already deleted by inotify on the old folder, we update our variables accordingly
        unset($foldersWatchedIds[$wd]);

        continue;
      } elseif ( // If it is an event IN_DELETE and is a file to watch
        ($mask & IN_DELETE) === IN_DELETE
        && (in_array($name, $phpEntriesToWatch)
          || in_array($name, $resourcesEntriesToWatch)
        )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'The file ' . returnLegiblePath($foldersWatchedIds[$wd], $name) .
            'has been deleted. We launch the appropriate tasks.' . PHP_EOL . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }
      } elseif ( // A save operation has been done
          (
            ($mask & IN_ATTRIB) === IN_ATTRIB
            || ($mask & IN_MODIFY) === IN_MODIFY
          )
          && (in_array($resourceName, $phpEntriesToWatch)
            || in_array($resourceName, $resourcesEntriesToWatch)
          )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          echo 'The file ' . returnLegiblePath($foldersWatchedIds[$wd], $name)
            . ' modified! We launch the appropriate tasks.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        if (in_array($resourceName, $phpEntriesToWatch))
        {
          // We generate the class mapping...
          require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

          // We updates routes configuration if the php file is a routes configuration file
          if ($name === 'Routes.php')
            require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
        } elseif (in_array($resourceName, $resourcesEntriesToWatch))
        {
          [
            $baseName,
            $resourceFolder,
            $resourcesMainFolder,
            $resourcesFolderEndPath
          ] = getPathInformations($name, $foldersWatchedIds[$wd]);

          if ($extension === 'ts')
          {
            generateJavaScript(
              GEN_WATCHER_VERBOSE,
              FILE_TASK_GCC,
              $resourceFolder,
              $baseName,
              $resourceName
            );
          } elseif (substr($baseName, 0, 1) !== '_')
          {
            $return = generateStylesheetsFiles(
              $baseName,
              $resourcesMainFolder,
              $resourcesFolderEndPath,
              $resourceName,
              $extension
            );

            if (GEN_WATCHER_VERBOSE > 0)
              $eventsDebug .= $return;
          } else
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

                $slashPosition = strrpos($mainResource, '/');
                $mainResourceFolder = realpath(substr($mainResource, 0, $slashPosition) . '/..');
                $mainResourceWithoutExtension = substr(
                  $mainResource,
                  $slashPosition + 1,
                  strrpos($mainResource, '.') - $slashPosition - 1
                );
                $generatedCssFile = $mainResourceWithoutExtension . '.css';

                // SASS / SCSS (Implemented for Dart SASS as Ruby SASS is deprecated, not tested with LibSass)
                $mainResourceCssFolder = $mainResourceFolder . '/css';

                // if the css folder corresponding to the sass/scss folder does not exist yet, we create it
                if (!file_exists($mainResourceCssFolder))
                  mkdir($mainResourceCssFolder);

                $cssPath = $mainResourceCssFolder . '/' . $generatedCssFile;

                [, $return] = cliCommand('sass --error-css ' . $mainResource . ':' . $cssPath);

                echo 'SASS / SCSS file ', returnLegiblePath($mainResource) . ' have generated ',
                  returnLegiblePath($cssPath), ' and ', returnLegiblePath($cssPath . '.map'), '.',
                  PHP_EOL . PHP_EOL;

                if (GEN_WATCHER_VERBOSE > 0)
                  $eventsDebug .= $return;
            }
          }
        }
      } elseif (GEN_WATCHER_VERBOSE > 1)
        $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);

      $headers = false;
    }

    if (GEN_WATCHER_VERBOSE > 0 && $eventsDebug !== '')
      echo $eventsDebug, PHP_EOL;
  }

  // Avoid watching too much to avoid performance issues
  usleep(100);
}


