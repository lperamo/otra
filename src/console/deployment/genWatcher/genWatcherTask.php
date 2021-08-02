<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genWatcher;

use FilesystemIterator;
use JetBrains\PhpStorm\Pure;
use otra\config\AllConfig;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\{BASE_PATH, CACHE_PATH, COMPILE_MODE_SAVE, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\
{ADD_BOLD,
  CLI_BASE,
  CLI_ERROR,
  CLI_GRAY,
  CLI_INFO,
  CLI_INFO_HIGHLIGHT,
  END_COLOR,
  ERASE_SEQUENCE,
  REMOVE_BOLD_INTENSITY,
  SUCCESS};
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
  GEN_WATCHER_ARG_NO_SASS_CACHE = 5,
  EXTENSIONS_TO_WATCH = ['php', 'ts', 'scss', 'sass'],
  EVENTS_TO_WATCH = IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR,

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
define(__NAMESPACE__ . '\\GEN_WATCHER_VERBOSE', (int) ($argv[GEN_WATCHER_ARG_VERBOSE] ?? 1));
define('NO_SASS_CACHE', isset($argv[GEN_WATCHER_ARG_NO_SASS_CACHE]) ? intval($argv[GEN_WATCHER_ARG_NO_SASS_CACHE]) : 0);

if (NO_SASS_CACHE !== 0 && NO_SASS_CACHE !== 1)
{
  echo CLI_ERROR, 'The argument ', CLI_INFO_HIGHLIGHT, 'no SASS cache', CLI_ERROR, ' must be ', CLI_INFO_HIGHLIGHT, 0,
    CLI_ERROR, ' or ', CLI_INFO_HIGHLIGHT, 1, CLI_ERROR, '.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

if (GEN_WATCHER_VERBOSE > 1)
{
  // Those constants are used in the maximum verbose mode only when we show the main events triggered
  define(
    __NAMESPACE__ . '\\WD_CONSTANTS',
    [
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
    ]
  );

  define(__NAMESPACE__ . '\\HEADER_EVENT_PADDING', 18);
  define(__NAMESPACE__ . '\\HEADER_COOKIE_PADDING', 7);
  define(__NAMESPACE__ . '\\HEADER_NAME_PADDING', 30);
  define(__NAMESPACE__ . '\\HEADER_WATCHED_RESOURCE_PADDING', 60);

  define(__NAMESPACE__ . '\\DATA_EVENT_PADDING', 22);
  define(__NAMESPACE__ . '\\DATA_COOKIE_PADDING', 11);
  define(__NAMESPACE__ . '\\DATA_NAME_PADDING', 34);
  define(__NAMESPACE__ . '\\DATA_WATCHED_RESOURCE_PADDING', 64);
}

if (WATCH_FOR_CSS_RESOURCES)
  define(__NAMESPACE__ . '\\SASS_TREE_CACHE_PATH', CACHE_PATH . 'css/sassTree.php');

define(
  __NAMESPACE__ . '\\EVENT_TO_TEST_FOR_SAVE',
  (!isset(AllConfig::$compileMode) || AllConfig::$compileMode === COMPILE_MODE_SAVE)
  ? IN_CLOSE_WRITE
  : IN_MODIFY
);

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
 * @param $fileDescriptor
 *
 * @throws OtraException
 * @return false|string
 */
function nonBlockRead($fileDescriptor) : false|string
{
  $stdin = [$fileDescriptor];
  $stderr = $stdout = [];
  $result = stream_select($stdin, $stdout, $stderr, 0);

  if ($result === false)
    throw new OtraException('stream_select failed');

  if ($result === 0)
    return false;

  return stream_get_line($fileDescriptor, 1);
}

/**
 * @param int    $binaryMask
 * @param int    $cookie
 * @param string $filename        Folder or file name
 * @param string $resource        Folder of file watched
 * @param bool   $mustShowHeaders Do we have to show the headers
 *
 * @return string The debug output
 */
function debugEvent(
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

/**
 * @param string $eventsDebug
 * @param string $messageBefore
 * @param string $filename
 * @param string $messageAfter
 * @param int    $binaryMask
 * @param int    $cookie
 * @param string $resourceToWatch
 * @param bool   $headers
 *
 * @return string
 */
function addVerboseInformation(
  string $eventsDebug,
  string $messageBefore,
  string $filename,
  string $messageAfter,
  int $binaryMask,
  int $cookie,
  string $resourceToWatch,
  bool $headers
) : string
{
  if (GEN_WATCHER_VERBOSE > 0)
  {
    $eventsDebug .= $messageBefore . returnLegiblePath($filename) . $messageAfter . PHP_EOL;

    if (GEN_WATCHER_VERBOSE > 1)
      $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $resourceToWatch, $headers);
  }

  return $eventsDebug;
}

/**
 * Deletes an asset and its source map.
 *
 * @param string $assetName
 * @param string $assetExtension
 *
 * @throws OtraException
 */
function deleteAsset(string $assetName, string $assetExtension)
{
  [
    $baseName,
    $resourcesMainFolder,
    $resourcesFolderEndPath
  ] = getPathInformations($assetName);

  $assetPath = $resourcesMainFolder . $assetExtension . '/' . substr($resourcesFolderEndPath,
      5) . $baseName . '.' . $assetExtension;
  unlink($assetPath);
  $assetMap = $assetPath . '.map';

  if (file_exists($assetMap))
    unlink($assetMap);
}

// Configuring inotify
$inotifyInstance = inotify_init();

// this is needed so inotify_read while operate in non blocking mode
// (we then can do echos when we are listening to events)
stream_set_blocking($inotifyInstance, false);

// ******************** ADDING WATCHES ********************
require CONSOLE_PATH . 'deployment/genWatcher/sassTools.php';
$resourcesEntriesToWatch = $phpEntriesToWatch = $foldersWatchedIds = [];

$dir_iterator = new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

// SASS/SCSS tree cache to optimize updates
$sassTree = [0 => [], 1 => [], 2 => []];
// SASS/SCSS resources (that have dependencies) that we have to watch
$sassMainResources = [];
$sassTreeDoesNotExist = !file_exists(SASS_TREE_CACHE_PATH);
$hasStartedCssTreeBuilding = false;

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
    if (
      // Does the resources path belongs to a valid defined path ? If yes, we process it
      isNotInThePath(PATHS_TO_HAVE_RESOURCES, $realPath)
      // starters are only meant to be copied, not used
      || str_contains($realPath, 'starters')
    )
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

        if (($extension === 'scss' || $extension === 'sass')
          && $mainResourceFilename[0] !== '_'
          && ($sassTreeDoesNotExist || NO_SASS_CACHE === 1))
        {
          if (!$hasStartedCssTreeBuilding)
          {
            echo 'Building the SASS/SCSS dependency tree...', PHP_EOL;
            $hasStartedCssTreeBuilding = true;
          }

          $sassMainResources[$mainResourceFilename] = $realPath;
          $sassTreeString = SASS_TREE_STRING_INIT;
          // We add the main sass file to the tree
          $sassTree[KEY_ALL_SASS][$realPath] = true;
          searchSassLastLeaves($sassTree, $realPath, $realPath, '.' . $extension, $sassTreeString);
        }
      }
    }
  }
}

// cleanup variables used to prepare the listening
unset(
  $argv,
  $dir_iterator,
  $entry,
  $extension,
  $hasStartedCssTreeBuilding,
  $isFolder,
  $iterator,
  $mainResourceFilename,
  $pathToAvoid,
  $realPath,
  // maybe those two variables have to been cleaned in TaskManager instead ?
  $task,
  $tasksClassMap
);

// If we are looking for SASS/SCSS resources then we certainly have created a dependency tree so we will now saving this
// tree into a cache ...unless we already have a cache ...in this case we retrieve this cache.
if (WATCH_FOR_CSS_RESOURCES)
{
  if ($sassTreeDoesNotExist || NO_SASS_CACHE === 1)
  {
    echo ERASE_SEQUENCE, 'SASS/SCSS dependency tree built', SUCCESS, 'Saving the SASS/SCSS dependency tree...', PHP_EOL;
    saveSassTree($sassTree);
  } else
  {
    echo 'Getting the SASS dependency tree...', PHP_EOL;
    $sassTree = require SASS_TREE_CACHE_PATH;
    echo ERASE_SEQUENCE, 'SASS dependency tree retrieved.', SUCCESS;
  }

  $sassTreeKeys = array_keys($sassTree[KEY_ALL_SASS]);
}

unset($sassTreeDoesNotExist);
// ******************** INTRODUCTION TEXT ********************

echo (GEN_WATCHER_VERBOSE > 0
  ? CLI_INFO . 'BASE_PATH' . CLI_BASE . ' is equal to ' . CLI_INFO_HIGHLIGHT . BASE_PATH . END_COLOR . PHP_EOL
  : CLI_BASE . 'Watcher started.' . END_COLOR)
  , 'Type ', CLI_INFO_HIGHLIGHT, 'q', CLI_BASE, ' to stop watching.',
  END_COLOR, PHP_EOL;

require CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';

// ******************** Watching ! ********************
// Allows to not be forced to type 'enter' after typing 'q'
system('stty cbreak -echo');
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

      $resourceName = $foldersWatchedIds[$watchDescriptor];

      if (is_dir($foldersWatchedIds[$watchDescriptor]))
        $resourceName .= DIR_SEPARATOR . $filename;

      // If it is a temporary file, we skip it
      if (str_contains(substr($resourceName, -2), '~'))
        continue;

      // If it is not a folder
      if (!(($binaryMask & IN_CREATE_DIR) === IN_CREATE_DIR
        || ($binaryMask & IN_DELETE_DIR) === IN_DELETE_DIR))
        // We store the extension without the dot
        $extension = substr($filename, strrpos($filename, '.') + 1);

      if ( // A save operation has been done
        (
          ($binaryMask & IN_ATTRIB) === IN_ATTRIB
          || ($binaryMask & EVENT_TO_TEST_FOR_SAVE) === EVENT_TO_TEST_FOR_SAVE
        )
        && (in_array($resourceName, $phpEntriesToWatch)
          || in_array($resourceName, $resourcesEntriesToWatch)
        )
      )
      {
        addVerboseInformation(
          $eventsDebug,
          'The file ',
          $filename,
          ' modified! We launch the appropriate tasks.',
          $binaryMask,
          $cookie,
          $foldersWatchedIds[$watchDescriptor] . DIR_SEPARATOR . $filename,
          $headers
        );

        if (in_array($resourceName, $phpEntriesToWatch))
          updatePHP($resourceName);
        elseif (in_array($resourceName, $resourcesEntriesToWatch))
        {
          [
            $baseName,
            $resourcesMainFolder,
            $resourcesFolderEndPath
          ] = getPathInformations($resourceName);

          /** @var string $extension */
          if ($extension === 'ts')
          {
            // 6 = length of devJs/
            $resourcesMainFolder = $resourcesMainFolder . 'js/' . substr($resourcesFolderEndPath, 6);

            generateJavaScript(
              true,
              GEN_WATCHER_VERBOSE,
              FILE_TASK_GCC,
              $resourcesMainFolder,
              $baseName,
              $resourceName
            );
          } else
          {
            preg_match_all(REGEX_SASS_IMPORT, file_get_contents($resourceName), $importedCssFound);
            $countImports = 0;
            $imports = [];

            // if there are imports, we have to check
            if (isset($importedCssFound[1][0]))
            {
              $matchedImports = $importedCssFound[1];

              foreach($matchedImports as $matchedImport)
              {
                $resourcesPath = $resourcesMainFolder . $resourcesFolderEndPath;
                [$newResourceToAnalyze] = getCssPathFromImport($matchedImport, '.' . $extension, $resourcesPath);
                $foundKey = array_search($newResourceToAnalyze, $sassTreeKeys);
                $imports[]= is_bool($foundKey) ? $newResourceToAnalyze : $foundKey;
              }

              $countImports = count($imports);
            }

            $sassFileKey = array_search($resourceName, $sassTreeKeys);

            // browsing the full SASS/SCSS dependency tree to know if the imports number has changed
            foreach ($sassTree[KEY_FULL_TREE] as $importingFile => &$importedFiles)
            {
              updateSassTreeAfterEvent(
                $sassTree,
                $sassTreeKeys,
                $extension,
                $sassFileKey,
                $importingFile,
                $importedFiles,
                $countImports,
                $imports
              );
            }

            saveSassTree($sassTree);

            if ($baseName[0] !== '_')
            {
              // If the file is meant to be used directly (this file will probably be the one that import the others)
              // like resource.scss
              $return = generateStylesheetsFiles(
                $baseName,
                $resourcesMainFolder,
                $resourcesFolderEndPath,
                $resourceName,
                $extension,
                GEN_WATCHER_VERBOSE > 0
              );
            } else
            {
              $return = '';

              // If the file is not meant to be used directly (this file will probably be imported by other ones)
              // like _resource.scss ... then we use our tree to speed up things
              foreach ($sassTree[KEY_MAIN_TO_LEAVES][array_search($resourceName, $sassTreeKeys)] as
                       $resourceToCompile)
              {
                $resourceFromTreeToCompile = $sassTreeKeys[$resourceToCompile];
                [$baseName, $resourcesMainFolder, $resourcesFolderEndPath, $extension] =
                  getPathInformations($resourceFromTreeToCompile);

                $return .= generateStylesheetsFiles(
                  $baseName,
                  $resourcesMainFolder,
                  $resourcesFolderEndPath,
                  $resourceFromTreeToCompile,
                  $extension,
                  GEN_WATCHER_VERBOSE > 0
                );
              }
            }

            echo PHP_EOL;

            if (GEN_WATCHER_VERBOSE > 0)
              $eventsDebug .= $return;
          }
        }
      } elseif ( // If it is an event IN_DELETE and is a file to watch
        ($binaryMask & IN_DELETE) === IN_DELETE
        && (in_array($resourceName, $phpEntriesToWatch)
          || in_array($resourceName, $resourcesEntriesToWatch)
        )
      )
      {
        addVerboseInformation(
          $eventsDebug,
          PHP_EOL . 'The file ',
          $filename,
          ' has been deleted. We remove related generated files.' . PHP_EOL,
          $binaryMask,
          $cookie,
          $foldersWatchedIds[$watchDescriptor] . DIR_SEPARATOR . $resourceName,
          $headers
        );

        // // We make sure not to watch this file again and we clean up related generated files
        if ($extension === 'scss' || $extension === 'sass')
        {
          unset($resourcesEntriesToWatch[array_search($resourceName, $resourcesEntriesToWatch)]);

          // removing the sass file from the sass dependency tree
          $sassKeyTreeToDelete = array_search($resourceName, $sassTreeKeys);

          foreach($sassTree[KEY_MAIN_TO_LEAVES] as $leave => &$mainSassFiles)
          {
            $mainFileToDelete = array_search($sassKeyTreeToDelete, $mainSassFiles);

            if ($mainFileToDelete !== false)
              unset($mainSassFiles[array_search($sassKeyTreeToDelete, $mainSassFiles)]);

            // If there are no more main files associated to the leave or the leave IS the file to delete
            // => we remove the leave
            if ($mainSassFiles === [] || $leave === $sassKeyTreeToDelete)
              unset($sassTree[KEY_MAIN_TO_LEAVES][$leave]);
          }

          // We now adjust the indexes because there is one less element and therefore the things do not match anymore
          $newMainToLeaves = [];

          foreach($sassTree[KEY_MAIN_TO_LEAVES] as $leave => $values)
          {
            $newLeave = ($leave > $sassKeyTreeToDelete) ? $leave - 1 : $leave;
            $newValues = [];

            foreach($values as $value)
            {
              $newValues[] = ($value > $sassKeyTreeToDelete) ? $value - 1 : $value;
            }
            $newMainToLeaves[$newLeave] = $newValues;
          }
          $sassTree[KEY_MAIN_TO_LEAVES] = $newMainToLeaves;

          // cleaning variables that will not be used anymore
          unset($leave, $value, $newMainToLeave);

          $newFullTree = [];

          foreach($sassTree[KEY_FULL_TREE] as $importingFile => $importedFiles)
          {
            if ($importingFile === $sassKeyTreeToDelete)
              continue;

            $newImportingFile = ($importingFile > $sassKeyTreeToDelete) ? $importingFile - 1 : $importingFile;
            $newFullTree[$newImportingFile] = createPrunedFullTree($sassKeyTreeToDelete, $importedFiles);
          }

          $sassTree[KEY_FULL_TREE] = $newFullTree;

          // removing the list of sass/scss files and cleaning variables that will not be used anymore
          unset($sassTree[KEY_ALL_SASS][$resourceName], $sassKeyTreeToDelete);

          saveSassTree($sassTree);

          // If the file is meant to be used directly (this file will probably be the one that import the others)
          // like resource.scss
          if ($filename[0] !== '_')
          {
            unset($sassMainResources[array_search($resourceName, $sassMainResources)]);
            deleteAsset($resourceName, 'css');
          }
        } elseif ($extension === 'ts')
        {
          unset($resourcesEntriesToWatch[array_search($resourceName, $resourcesEntriesToWatch)]);
          deleteAsset($resourceName, 'js');
        }
        elseif ($extension === 'php')
        {
          unset($phpEntriesToWatch[array_search($resourceName, $phpEntriesToWatch)]);
          updatePHP($resourceName);
        }
      } elseif ( // If it is an event IN_CREATE and is a file to watch
        ($binaryMask & IN_CREATE) === IN_CREATE
        && (
          !isNotInThePath(PATHS_TO_HAVE_PHP, $resourceName)
          || !isNotInThePath(PATHS_TO_HAVE_RESOURCES, $resourceName)
        )
      )
      {
        // If this is not a file that we want to watch, we skip it.
        if (!in_array($extension, EXTENSIONS_TO_WATCH))
          continue;

        $foldersWatchedIds[inotify_add_watch($inotifyInstance, $resourceName, EVENTS_TO_WATCH)] = $resourceName;

        if ($extension === 'scss' || $extension === 'sass')
        {
          $resourcesEntriesToWatch[] = $resourceName;
          $sassMainResources[$filename] = $resourceName;

          // We add the new resource to the SASS/SCSS dependency tree
          $sassTree[KEY_ALL_SASS][$resourceName] = true;
        } elseif ($extension === 'ts')
          $resourcesEntriesToWatch[] = $resourceName;
        elseif ($extension === 'php')
          $phpEntriesToWatch[] = $resourceName;

        addVerboseInformation(
          $eventsDebug,
          PHP_EOL . 'We are now watching the file ',
          $filename,
          '.',
          $binaryMask,
          $cookie,
          $foldersWatchedIds[$watchDescriptor],
          $headers
        );
      } elseif (($binaryMask & IN_CREATE_DIR) === IN_CREATE_DIR)
      {
        // Adding a watch on the new folder
        // User is adding a folder
        $foldersWatchedIds[inotify_add_watch(
          $inotifyInstance,
          $resourceName,
          IN_ALL_EVENTS ^ IN_CLOSE_NOWRITE ^ IN_OPEN ^ IN_ACCESS | IN_ISDIR
        )] = $resourceName;

        addVerboseInformation(
          $eventsDebug,
          PHP_EOL . 'Creating the folder ',
          $resourceName,
          '. We now watching it.',
          $binaryMask,
          $cookie,
          $foldersWatchedIds[$watchDescriptor],
          $headers
        );

        continue;
      } elseif (($binaryMask & IN_DELETE_DIR) === IN_DELETE_DIR)
      {
        // User is deleting a folder
        addVerboseInformation(
          $eventsDebug,
          PHP_EOL . 'Deleting the folder ',
          $resourceName,
          '. We do not watch it anymore.',
          $binaryMask,
          $cookie,
          $foldersWatchedIds[$watchDescriptor],
          $headers
        );

        // A watch has been already deleted by inotify on the old folder, we update our variables accordingly
        unset($foldersWatchedIds[$watchDescriptor]);

        continue;
      } elseif (GEN_WATCHER_VERBOSE > 1)
          $eventsDebug .= debugEvent($binaryMask, $cookie, $filename, $foldersWatchedIds[$watchDescriptor], $headers);

      $headers = false;
    }

    if (GEN_WATCHER_VERBOSE > 0 && $eventsDebug !== '')
      echo $eventsDebug, PHP_EOL;
  }

  if (nonBlockRead(STDIN) === 'q')
    break;

  // Avoid watching too much to avoid performance issues
  usleep(100);
}
