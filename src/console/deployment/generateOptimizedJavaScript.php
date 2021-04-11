<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\OtraException;
use function otra\tools\files\returnLegiblePath;

define('OTRA_LABEL_TSCONFIG_JSON', 'tsconfig.json');

/**
 * @param int    $verbose
 * @param bool   $mustLaunchGcc
 * @param string $resourceFolder
 * @param string $baseName
 * @param string $resourceName
 *
 * @throws OtraException
 */
function generateJavaScript(
  int $verbose,
  bool $mustLaunchGcc,
  string $resourceFolder,
  string $baseName,
  string $resourceName
) : void
{
  /* TypeScript seems to not handle the compilation of one file using the json configuration file !
         * It is either the entire project with the json configuration file
         * or a list of files without json configuration ... but not a list with json configuration ...
         * so we create one temporary json that list only the file we want */

  /** @var array|null $typescriptConfig */
  $typescriptConfig = json_decode(file_get_contents(BASE_PATH . OTRA_LABEL_TSCONFIG_JSON), true);

  if ($typescriptConfig !== null)
  {
    // The Google Closure Compiler application cannot overwrite a file so we have to create a temporary one
    // and remove the dummy file ...
    // if the js folder corresponding to the ts folder does not exist yet, we create it as well as its subfolders
    if (!file_exists($resourceFolder))
      mkdir($resourceFolder, 0777, true);

    $generatedTemporaryJsFile = $resourceFolder . $baseName . '_viaTypescript.js';
    $generatedJsFile = $resourceFolder . $baseName . '.js';

    // Creating a temporary typescript json configuration file suited for the OTRA watcher.
    // We need to recreate it each time because the user can alter his original configuration file
    $typescriptConfig['files'] = [$resourceName];
    $typescriptConfig['compilerOptions']['outFile'] = $generatedTemporaryJsFile;
    unset($typescriptConfig['compilerOptions']['watch']);

    $temporaryTypescriptConfig = BASE_PATH . 'tsconfig_tmp.json';
    $filePointer = fopen($temporaryTypescriptConfig, 'w');
    // The flags for 'json_encode' allows better debugging
    // (otherwise tsc will say that the bug is on the first line ..and the first line represents ALL the json)
    fwrite(
      $filePointer,
      json_encode(
        $typescriptConfig,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK)
    );
    fclose($filePointer);
    unset($filePointer);

    /* Launches typescript compilation on the file with project json configuration
       and launches Google Closure Compiler on the output just after */
    [, $output] = cliCommand('/usr/bin/tsc -p ' . $temporaryTypescriptConfig);

    unlink($temporaryTypescriptConfig);

    $legibleCreatedTemporaryJsFile = returnLegiblePath($generatedTemporaryJsFile);
    $jsFileExists = file_exists($generatedTemporaryJsFile);
    $temporarySourceMap = $generatedTemporaryJsFile . '.map';

    if (!$jsFileExists)
    {
      echo CLI_YELLOW,
        'Something was wrong during typescript compilation but this may not be blocking. Maybe you have a problem with the ',
        CLI_LIGHT_CYAN, OTRA_LABEL_TSCONFIG_JSON, CLI_YELLOW, ' file.', END_COLOR, PHP_EOL, $output;
      throw new \otra\OtraException('', 1, '', NULL, [], true);
    }

    if ($verbose > 0)
    {
      echo CLI_GREEN, 'TypeScript file ', returnLegiblePath($resourceName, '', false), CLI_GREEN,
        ' have generated the temporary file';

      if (file_exists($temporarySourceMap))
        echo 's ', $legibleCreatedTemporaryJsFile, CLI_GREEN, ' and ',
          returnLegiblePath($generatedTemporaryJsFile . '.map', '', false);
      else
        echo ' ', $legibleCreatedTemporaryJsFile;

      echo CLI_GREEN, '.', END_COLOR, PHP_EOL, PHP_EOL;
    }

    // We launch Google Closure Compiler only if a file has been generated with success but...
    // should we launch Google Closure Compiler ?
    if ($mustLaunchGcc)
    {
      if ($verbose > 0)
        echo 'Launching Google Closure Compiler...', PHP_EOL;

      // TODO add those lines to handle class map and fix the resulting issue
      // ' --create_source_map --source_map_input ' . $generatedTemporaryJsFile . '.map'
      // We do not launch an exception on error to avoid stopping the execution of the watcher
      [, $output] = cliCommand(
        'java -jar ' . CONSOLE_PATH . 'deployment/compiler.jar -W '
        . GOOGLE_CLOSURE_COMPILER_VERBOSITY[$verbose]
        . ' -O ADVANCED --rewrite_polyfills=false --js ' . $generatedTemporaryJsFile . ' --js_output_file '
        . $generatedJsFile,
        CLI_RED . 'A problem occurred.' . END_COLOR . $output . PHP_EOL,
        false
      );

      if ($verbose > 0)
        echo $output, CLI_GREEN, 'Javascript ', returnLegiblePath($generatedJsFile), ' has been optimized.', PHP_EOL;

      // Cleaning temporary files ...(js and the mapping)
      unlink($generatedTemporaryJsFile);

      if (file_exists($temporarySourceMap))
        unlink($temporarySourceMap);

      /** TODO side note, the class mapping is not present when we pass by Google Closure.
       * We have to check if we can add it.
       */
    } else
    {
      rename($generatedTemporaryJsFile, $generatedJsFile);
      $sourceMapFile = $generatedTemporaryJsFile . '.map';

      // If there was a source map, rename it as well
      if (file_exists($sourceMapFile))
      {
        rename($generatedTemporaryJsFile . '.map', $generatedJsFile . '.map');
        // Fixing class mapping reference
        file_put_contents(
          $generatedJsFile,
          str_replace(
            $baseName . '_viaTypescript.js.map',
            $baseName . '.js.map',
            file_get_contents($generatedJsFile)
          )
        );
      }
    }

    // We copy the service worker to the root if there is one
    if ($baseName === 'sw' || $baseName === 'serviceWorker')
    {
      echo 'Moving the service worker files to their final locations...', PHP_EOL;

      $serviceWorkerPath = BASE_PATH . 'web/' . $baseName . '.js';

      if (!rename($generatedJsFile, $serviceWorkerPath))
      {
        echo CLI_RED, 'Problem while moving the generated service worker file.', END_COLOR, PHP_EOL;
        throw new OtraException('', 1, '', NULL, [], true);
      }

      $generatedJsMapFile = $generatedJsFile . '.map';
      $newJsMapPath = $serviceWorkerPath . '.map';

      if (file_exists($generatedJsMapFile) && !rename($generatedJsMapFile, $newJsMapPath))
      {
        echo CLI_RED, 'Problem while moving the generated service worker file mapping.', END_COLOR, PHP_EOL;
        throw new OtraException('', 1, '', NULL, [], true);
      }

      echo 'Service worker files moved to ', CLI_LIGHT_CYAN, returnLegiblePath($serviceWorkerPath), END_COLOR,
        ' and ', CLI_LIGHT_CYAN, returnLegiblePath($newJsMapPath), END_COLOR, '.', PHP_EOL;
    }
  } else
    echo 'There is an error with your ', returnLegiblePath(OTRA_LABEL_TSCONFIG_JSON), ' file. : '
      , CLI_RED, json_last_error_msg(), PHP_EOL;
}

