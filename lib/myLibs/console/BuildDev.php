<?php

namespace lib\myLibs\console;

use SebastianBergmann\CodeCoverage\Report\PHP;

require BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/Cli.php';

const BUILD_DEV_ARG_VERBOSE = 2,
      BUILD_DEV_ARG_MASK = 3,
      BUILD_DEV_ARG_GCC = 4,
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
        BASE_PATH . 'lib'
      ],

      PATHS_TO_HAVE_RESOURCES =
      [
        BASE_PATH . 'bundles',
        BASE_PATH . 'lib'
      ],

      PATH_TO_AVOID = BASE_PATH . 'bundles/config';

// Reminder : 0 => no debug, 1 => basic logs
define('BUILD_DEV_VERBOSE', array_key_exists(BUILD_DEV_ARG_VERBOSE, $argv) ? $argv[BUILD_DEV_ARG_VERBOSE] : 0);

define(
  'BUILD_DEV_GCC',
  array_key_exists(BUILD_DEV_ARG_GCC, $argv) === true && $argv[BUILD_DEV_ARG_GCC] === 'true' ? true : false
);

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
 * @param string    $resource Most of the time the name of a folder
 * @param string    $name     Most of the time the name of a file
 * @param bool|null $endColor Do we have to reset color at the end ?
 *
 * @return string
 */
function returnLegiblePath(string $resource, ?string $name = '', ?bool $endColor = true) : string
{
  // Avoid to finish with '/' if $resource is not a folder (and then $name = '')
  if ($name !== '')
    $name = '/' . $name;

  return (strpos($resource, BASE_PATH) !== false
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
$isWatched = function (array &$argv, bool &$maskExists, int $genWatcherMask) : bool
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

// Handle PHP files
if (WATCH_FOR_PHP_FILES === true)
{
  // We generate the class mapping...
  Tasks::genClassMap();

  // We updates routes configuration if the php file is a routes configuration file
  echo 'Launching routes update...', PHP_EOL;
  Tasks::upConf();
}

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

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

    $extension = $entry->getExtension();
    $baseName = substr($entry->getFilename(), 0, -strlen($extension) - 1);
    $resourceName = $entry->getPathname();
    $resourceFolder = dirname($resourceName);

    if ($extension === 'ts')
    {
      /* TypeScript seems to not handle the compilation of one file using the json configuration file !
       * It is either the entire project with the json configuration file
       * or a list of files without json configuration ... but not a list with json configuration ...
       * so we create one temporary json that list only the file we want */

      $typescriptConfig = json_decode(file_get_contents(BASE_PATH . 'tsconfig.json'), true);

      if ($typescriptConfig !== null)
      {
        // The Google Closure Compiler application cannot overwrite a file so we have to create a temporary one
        // and remove the dummy file ...
        $generatedTemporaryJsFile = $resourceFolder . '/js/' . $baseName . '_viaTypescript.js';
        $generatedJsFile = $resourceFolder . '/js/' . $baseName . '.js';

        // Creating a temporary typescript json configuration file suited for the OTRA watcher.
        // We need to recreate it each time because the user can alter his original configuration file
        $typescriptConfig['files']                      = [$resourceName];
        $typescriptConfig['compilerOptions']['outFile'] = $generatedTemporaryJsFile;
        unset($typescriptConfig['compilerOptions']['watch']);

        $temporaryTypescriptConfig = BASE_PATH . '/tsconfig_tmp.json';
        $fp = fopen($temporaryTypescriptConfig, 'w');
        // JSON_PRETTY_PRINT allows better debugging
        // (otherwise tsc will say that the bug is on the first line ..and the first line represents ALL the json)
        fwrite($fp, json_encode($typescriptConfig, JSON_PRETTY_PRINT));
        fclose($fp);

        /* Launches typescript compilation on the file with project json configuration
           and launches Google Closure Compiler on the output just after */
        list($return, $output) = cli('/usr/bin/tsc -p ' . $temporaryTypescriptConfig);

        unlink($temporaryTypescriptConfig);

        $legibleGeneratedTemporaryJsFile = returnLegiblePath($generatedTemporaryJsFile);
        $jsFileExists = file_exists($generatedTemporaryJsFile);

        if ($return === 0 && $jsFileExists === true)
          echo CLI_GREEN, 'TypeScript file ', returnLegiblePath($resourceName, '',
            false), CLI_GREEN, ' have generated the temporary files ', $legibleGeneratedTemporaryJsFile, ' and ',
          returnLegiblePath($generatedTemporaryJsFile . '.map', '', false), CLI_GREEN, '.',
          END_COLOR, PHP_EOL, PHP_EOL;
        else
        {
          if ($jsFileExists === true)
            echo CLI_BROWN, 'Something was wrong during typescript compilation but this may not be blocking.',
            END_COLOR, PHP_EOL, $output, 'Launching Google Closure Compiler...', PHP_EOL;
          else
            echo CLI_RED, 'The javascript cannot be generated ! Maybe you have a problem with the ',
            CLI_LIGHT_CYAN,
            'tsconfig.json', CLI_RED,  ' file.', END_COLOR, PHP_EOL, $output;
        }

        // We launch Google Closure Compiler only if a file has been generated with success
        if ($jsFileExists === true)
        {
          // Should we launch Google Closure Compiler ?
          if (BUILD_DEV_GCC === true)
          {
            // TODO add those lines to handle class map and fix the resulting issue
            // ' --create_source_map --source_map_input ' . $generatedTemporaryJsFile . '.map'
            list($return, $output) = cli('java -jar ' . BASE_PATH . 'lib/myLibs/console/compiler.jar -W '
              . GOOGLE_CLOSURE_COMPILER_VERBOSITY[BUILD_DEV_VERBOSE]
              . ' -O ADVANCED --rewrite_polyfills=false --js ' . $generatedTemporaryJsFile . ' --js_output_file '
              . $generatedJsFile);

            if (BUILD_DEV_VERBOSE > 0)
            {
              echo ($return === 0) ?
                $output . CLI_GREEN . 'Javascript ' . returnLegiblePath($generatedJsFile) . ' has been optimized.'
                . PHP_EOL : CLI_RED . 'A problem occurred.' . END_COLOR . $output . PHP_EOL;
            }

            // Cleaning temporary files ...(js and the mapping)
            unlink($generatedTemporaryJsFile);
            unlink($generatedTemporaryJsFile . '.map');
          } else {
            rename($generatedTemporaryJsFile, $generatedJsFile);
            rename($generatedTemporaryJsFile . '.map', $generatedJsFile . '.map');
          }
        }
      } else
        echo 'There is an error with your ', returnLegiblePath('tsconfig.json'), ' file. : '
        , CLI_RED, json_last_error_msg(), PHP_EOL;

    } elseif (substr($baseName, 0, 1) !== '_')
    {
      $generatedCssFile = $baseName . '.css';

      // SASS / SCSS (Implemented for Dart SASS as Ruby SASS is deprecated, not tested with LibSass)
      $cssPath = realPath($resourceFolder . '/../css') . '/' . $generatedCssFile;
      list(, $return) = cli('sass ' . $resourceName . ':' . $cssPath);

      echo strtoupper($extension) . ' file ', returnLegiblePath($resourceName) . ' have generated ',
        returnLegiblePath($cssPath) . ' and ', returnLegiblePath($cssPath . '.map'), '.',
        PHP_EOL . PHP_EOL;
    }
  }
}

unset($dir_iterator, $iterator, $entry, $realPath);

?>