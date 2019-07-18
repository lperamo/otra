<?php

namespace lib\myLibs\console;

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/Cli.php';

use lib\myLibs\console\Tasks;

// TODO Add parameter(s)? to add folder(s) to exclude from watching
// TODO Improve fineness of the folders to explore, path (PATHS_TO_HAVE_PHP, PATHS_TO_HAVE_RESOURCES more precises etc.)
// TODO We need to allow classic JavaScript files if the developers do not want to use TypeScript for their project.
// TODO Handle the "rename" event

// Initialization
const GEN_WATCHER_ARG_VERBOSE = 2;
const GEN_WATCHER_ARG_MASK = 3;
const GEN_WATCHER_MASK_SCSS = 1;
const GEN_WATCHER_MASK_TS = 2;
const GEN_WATCHER_MASK_ROUTES = 4;
const GEN_WATCHER_MASK_PHP = 8;
const EXTENSIONS_TO_WATCH = ['php', 'ts', 'scss', 'sass'],
  RESOURCES_TO_WATCH = ['ts', 'scss', 'sass'],

  PATHS_TO_HAVE_PHP =
  [
    BASE_PATH . 'bundles',
    BASE_PATH . 'config',
    BASE_PATH . 'lib'
  ],

  PATHS_TO_HAVE_RESOURCES =
  [
    BASE_PATH . 'bundles',
    BASE_PATH . 'lib'
  ],

  PATH_TO_AVOID = BASE_PATH . 'bundles/config',

  // Those variables have the same name for folders so we rename those for more clarity (PHP 7.3 at time of writing)
  IN_CLOSE_NOWRITE_DIR = 1073741840,
  IN_OPEN_DIR = 1073741856,
  IN_CREATE_DIR = 1073742080,
  IN_DELETE_DIR = 1073742336;

// Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
define('GEN_WATCHER_VERBOSE', array_key_exists(GEN_WATCHER_ARG_VERBOSE, $argv) ? $argv[GEN_WATCHER_ARG_VERBOSE] : 0);

/**
 * @param array  $paths
 * @param string $realPath
 *
 * @return bool
 */
function isNotInThePath(array $paths, string &$realPath) : bool
{
  $continue = true;

  foreach ($paths as &$path)
  {
    // If we found a valid base path in the actual path
    if (mb_strpos($realPath, $path) !== false){
      $continue = false;
    }
  }

  return $continue;
}

/**
 * Returns BASE_PATH the/path with BASE_PATH in light blue whether the resource is contained in the BASE_PATH
 * otherwise returns resource name as is.
 *
 * @param string $resource Most of the time the name of a folder
 * @param string $name     Most of the time the name of a file
 *
 * @return string
 */
function returnLegiblePath(string $resource, ?string $name = '') : string
{
  // Avoid to finish with '/' if $resource is not a folder (and then $name = '')
  if ($name !== '')
    $name = '/' . $name;

  return strpos($resource, BASE_PATH) !== false
    ? lightBlueText('BASE_PATH ') . cyanText(substr($resource, strlen(BASE_PATH)) . $name)
    : cyanText($resource . $name);
}

/**
 * @param int    $mask
 * @param int    $cookie
 * @param string $name     Folder or file name
 * @param string $resource Folder of file watched
 * @param bool   $headers  Do we have to show the headers
 *
 * @return string The debug output
 */
function debugEvent(int &$mask, int &$cookie, string &$name, string &$resource, bool &$headers = false) : string
{
  $wd_constants = [
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
  ];

  $debugToPrint = '';

  if ($headers === true)
    // Headers
    $debugToPrint .= str_pad('event', 18)
       . str_pad('cookie', 7)
       . str_pad('name', 30)
       . str_pad('resource watched', 60)
       . PHP_EOL;

  // Data
  $debugToPrint .= str_pad($wd_constants[$mask], 18)
    . str_pad($cookie, 7)
    . str_pad($name, 30);

  return $debugToPrint . str_pad(returnLegiblePath($resource), 60) . PHP_EOL;
}

/**
 * @param array $argv       Command line arguments
 * @param bool  $maskExists
 * @param int   $genWatcherMask
 *
 * @return bool
 */
$isWatched = function (array &$argv, bool &$maskExists, int $genWatcherMask) : bool
{
  return (
      $maskExists === true
      && ($argv[GEN_WATCHER_ARG_MASK] & $genWatcherMask) === $genWatcherMask
    )
    || $maskExists === false;
};

$maskExists = array_key_exists(GEN_WATCHER_ARG_MASK, $argv);

define('WATCH_FOR_CSS_RESOURCES', $isWatched($argv, $maskExists, GEN_WATCHER_MASK_SCSS));
define('WATCH_FOR_TS_RESOURCES', $isWatched($argv, $maskExists, GEN_WATCHER_MASK_TS));
define('WATCH_FOR_PHP_FILES', $isWatched($argv, $maskExists, GEN_WATCHER_MASK_PHP));

unset($isWatched);

define(
  'WATCH_FOR_ROUTES',
  (
    $maskExists === true
    && ($argv[GEN_WATCHER_ARG_MASK] & GEN_WATCHER_MASK_ROUTES) === GEN_WATCHER_MASK_ROUTES
  )
  || $maskExists === false
);

unset($maskExists);

// Configuring inotify
$inotifyInstance = inotify_init();

// this is needed so inotify_read while operate in non blocking mode
// (we then can do echos when we are listening to events)
stream_set_blocking($inotifyInstance, 0);

// ******************** ADDING WATCHS ********************

$resourcesEntriesToWatch = $phpEntriesToWatch = $foldersWatchedIds = [];

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $folder = $entry->isDir();
  $extension = $entry->getExtension();

  if (in_array($extension, EXTENSIONS_TO_WATCH) === false && $folder === false)
    continue;

  $realPath = $entry->getRealPath();
  $haveBeenWatched = false;

  if (mb_strpos($realPath, PATH_TO_AVOID) !== false)
    continue;

  // Adding watches for PHP files if needed
  if (WATCH_FOR_PHP_FILES === true)
  {
    // Does the PHP path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(PATHS_TO_HAVE_PHP, $realPath) === true)
      continue;

    if ($extension === 'php' || $folder === true)
    {
      $phpEntriesToWatch[] = $realPath;

      if ($folder === true)
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
  if ($haveBeenWatched === false && (WATCH_FOR_CSS_RESOURCES || WATCH_FOR_TS_RESOURCES))
  {
    // Does the resources path belongs to a valid defined path ? If yes, we process it
    if (isNotInThePath(PATHS_TO_HAVE_RESOURCES, $realPath) === true)
      continue;

    if (in_array($extension, RESOURCES_TO_WATCH) === true|| $folder === true)
    {
      $resourcesEntriesToWatch[] = $realPath;

      if ($folder === true)
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $realPath,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $realPath;
    }
  }
}

unset($dir_iterator, $iterator, $entry, $realPath);

// ******************** INTRODUCTION TEXT ********************

if (GEN_WATCHER_VERBOSE > 0)
  echo lightBlueText('BASE_PATH') . ' is equal to ' . cyanText(BASE_PATH) . PHP_EOL . PHP_EOL;

// ******************** Watching ! ********************
while (true)
{
  $events = inotify_read($inotifyInstance);
  $headers = true;

  if ($events !== false)
  {
    $eventsDebug = '';

    // Loop though the events which occurred
    foreach ($events as &$eventDetails)
    {
      /**
       * @var int    $wd
       * @var int    $mask
       * @var int    $cookie
       * @var string $name
       */
      extract($eventDetails);

      // IN_OPEN || IN_MOVED_FROM
      if ($mask === 96)
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
          $eventsDebug .=  PHP_EOL . 'Creating the folder ' . returnLegiblePath($folderPath) . '. We now watching it.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        continue;
      } else if (($mask & IN_DELETE_DIR) === IN_DELETE_DIR)
      {
        // User is deleting a folder
        $folderPath = $foldersWatchedIds[$wd] . '/' . $name;

        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'Deleting the folder ' . returnLegiblePath($folderPath) . '. We do not watch it anymore.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        // A watch has been already deleted by inotify on the old folder, we update our variables accordingly
        unset($foldersWatchedIds[$wd]);

        continue;
      } else if ( // If it is an event IN_DELETE and is a file to watch
        ($mask & IN_DELETE) === IN_DELETE
        && (in_array($name, $phpEntriesToWatch) === true
          || in_array($name, $resourcesEntriesToWatch) === true
        )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          $eventsDebug .= PHP_EOL . 'The file ' . returnLegiblePath($foldersWatchedIds[$wd], $name) . 'has been deleted. We launch the appropriate tasks.' . PHP_EOL . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }
      } else if ( // A save operation has been done
          (
            ($mask & IN_ATTRIB) === IN_ATTRIB
            || ($mask & IN_MODIFY) === IN_MODIFY
          )
          && (in_array($resourceName, $phpEntriesToWatch) === true
            || in_array($resourceName, $resourcesEntriesToWatch) === true
          )
      )
      {
        if (GEN_WATCHER_VERBOSE > 0)
        {
          echo 'The file ' . returnLegiblePath($foldersWatchedIds[$wd], $name) . ' modified! We launch the appropriate tasks.' . PHP_EOL;

          if (GEN_WATCHER_VERBOSE > 1)
            $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
        }

        if (in_array($resourceName, $phpEntriesToWatch) === true)
        {
          // We generate the class mapping...
          Tasks::genClassMap();

          // We updates routes configuration if the php file is a routes configuration file
          if ($name === 'Routes.php')
            Tasks::upConf();
        } else if (in_array($resourceName, $resourcesEntriesToWatch) === true)
        {
          $fileInformations = explode('.', $name);
          $resourceFolder  = dirname($foldersWatchedIds[$wd]);

          if ($fileInformations[1] === 'ts')
          {
            /* TypeScript seems to not handle the compilation of one file using the json configuration file !
             * It is either the entire project with the json configuration file
             * or a list of files without json configuration ... but not a list with json configuration ...
             * so we create one temporary json that list only the file we want */

            $typescriptConfig = json_decode(file_get_contents(BASE_PATH . 'tsconfig.json'), true);

            if ($typescriptConfig !== null)
            {
              $generatedJsFile = $resourceFolder . '/js/' . $fileInformations[0] . '.js';

              // Creating a temporary typescript json configuration file suited for the OTRA watcher.
              // We need to recreate it each time because the user can alter his original configuration file
              $typescriptConfig['files']                      = [$resourceName];
              $typescriptConfig['compilerOptions']['outFile'] = $generatedJsFile;
              unset($typescriptConfig['compilerOptions']['watch']);

              $temporaryTypescriptConfig = BASE_PATH . '/tsconfig_tmp.json';
              $fp                        = fopen($temporaryTypescriptConfig, 'w');
              fwrite($fp, json_encode($typescriptConfig));
              fclose($fp);

              /* Launches typescript compilation on the file with project json configuration
                 and launches Google Closure Compiler on the output just after */
              list(, $return) = cli(
                '(/usr/bin/tsc --typeRoots ' . BASE_PATH . 'node_modules/@types --project '
                . $temporaryTypescriptConfig . ' || echo \'Errors to fix but these are not blocking.\') && java -jar '
                . BASE_PATH . 'lib/myLibs/console/compiler.jar --compilation_level ADVANCED_OPTIMIZATIONS --rewrite_polyfills=false --js '
                . $generatedJsFile . ' --js_output_file ' . $generatedJsFile);

              unlink($temporaryTypescriptConfig);

              echo 'TypeScript file ', returnLegiblePath($resourceName) . ' have generated ',
                returnLegiblePath($generatedJsFile) . ' and ', returnLegiblePath($generatedJsFile . '.map'), '.',
              PHP_EOL . PHP_EOL;

              if (GEN_WATCHER_VERBOSE > 0)
                $eventsDebug .= $return;
            } else
              echo 'There is an error with your ', returnLegiblePath('tsconfig.json'), ' file. : ' . redText(json_last_error_msg()), PHP_EOL;

          } else
          {
            $generatedCssFile = $fileInformations[0] . '.css';

            // SASS / SCSS (Implemented for Dart SASS as Ruby SASS is deprecated, not tested with LibSass)
            $cssPath = realPath($resourceFolder . '/css') . '/' . $generatedCssFile;
            list(, $return) = cli('sass ' . $resourceName . ':' . $cssPath);

            echo 'SASS / SCSS file ', returnLegiblePath($resourceName) . ' have generated ',
              returnLegiblePath($cssPath) . ' and ', returnLegiblePath($cssPath . '.map'), '.',
            PHP_EOL . PHP_EOL;

            if (GEN_WATCHER_VERBOSE > 0)
              $eventsDebug .= $return;
          }
        }
      } else
      {
        if (GEN_WATCHER_VERBOSE > 1)
          $eventsDebug .= debugEvent($mask, $cookie, $name, $foldersWatchedIds[$wd], $headers);
      }

      $headers = false;
    }

    if (GEN_WATCHER_VERBOSE > 0 && $eventsDebug !== '')
      echo $eventsDebug . PHP_EOL;
  }

  // Avoid watching too much to avoid performance issues
  sleep(0.1);
}
?>

