<?php
declare(strict_types=1);

use otra\OtraException;
use function otra\console\returnLegiblePath;
use const otra\console\GOOGLE_CLOSURE_COMPILER_VERBOSITY;

function generateJavaScript(
  int $verbose,
  bool $launch,
  string &$resourceFolder,
  string &$baseName,
  string &$resourceName
) : void
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
    $jsFolder = $resourceFolder . 'js/';

    // if the js folder corresponding to the ts folder does not exist yet, we create it as well as its subfolders
    if (file_exists($jsFolder) === false)
      mkdir($jsFolder, 0777, true);

    $generatedTemporaryJsFile = $jsFolder . $baseName . '_viaTypescript.js';
    $generatedJsFile = $jsFolder . $baseName . '.js';

    // Creating a temporary typescript json configuration file suited for the OTRA watcher.
    // We need to recreate it each time because the user can alter his original configuration file
    $typescriptConfig['files'] = [$resourceName];
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
    $temporarySourceMap = $generatedTemporaryJsFile . '.map';

    if ($return === 0 && $jsFileExists === true)
    {
      if (BUILD_DEV_VERBOSE > 0)
      {
        echo CLI_GREEN, 'TypeScript file ', returnLegiblePath($resourceName, '',
          false), CLI_GREEN, ' have generated the temporary file';

        if (file_exists($temporarySourceMap))
          echo 's ', $legibleGeneratedTemporaryJsFile, CLI_GREEN, ' and ',
          returnLegiblePath($generatedTemporaryJsFile . '.map', '', false);
        else
          echo ' ', $legibleGeneratedTemporaryJsFile;

        echo CLI_GREEN, '.', END_COLOR, PHP_EOL, PHP_EOL;
      }

    } elseif ($jsFileExists === true)
    {
      echo CLI_YELLOW, 'Something was wrong during typescript compilation but this may not be blocking.',
      END_COLOR, PHP_EOL, $output;
    } else
    {
      echo CLI_RED, 'The javascript cannot be generated ! Maybe you have a problem with the ',
      CLI_LIGHT_CYAN,
      'tsconfig.json', CLI_RED, ' file.', END_COLOR, PHP_EOL, $output;
    }

    // We launch Google Closure Compiler only if a file has been generated with success
    if ($jsFileExists === true)
    {
      // Should we launch Google Closure Compiler ?
      if ($launch === true)
      {
        echo 'Launching Google Closure Compiler...', PHP_EOL;

        // TODO add those lines to handle class map and fix the resulting issue
        // ' --create_source_map --source_map_input ' . $generatedTemporaryJsFile . '.map'
        list($return, $output) = cli('java -jar ' . CONSOLE_PATH . 'deployment/compiler.jar -W '
          . GOOGLE_CLOSURE_COMPILER_VERBOSITY[$verbose]
          . ' -O ADVANCED --rewrite_polyfills=false --js ' . $generatedTemporaryJsFile . ' --js_output_file '
          . $generatedJsFile);

        if ($verbose > 0)
        {
          echo ($return === 0) ?
            $output . CLI_GREEN . 'Javascript ' . returnLegiblePath($generatedJsFile) . ' has been optimized.'
            . PHP_EOL : CLI_RED . 'A problem occurred.' . END_COLOR . $output . PHP_EOL;
        }

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

        if (file_exists($generatedJsMapFile))
        {
          if (!rename($generatedJsMapFile, $newJsMapPath))
          {
            echo CLI_RED, 'Problem while moving the generated service worker file mapping.', END_COLOR, PHP_EOL;
            throw new OtraException('', 1, '', NULL, [], true);
          }
        }

        echo 'Service worker files moved to ', CLI_LIGHT_CYAN, returnLegiblePath($serviceWorkerPath), END_COLOR,
          ' and ', CLI_LIGHT_CYAN, returnLegiblePath($newJsMapPath), END_COLOR, '.', PHP_EOL;
      }
    }
  } else
    echo 'There is an error with your ', returnLegiblePath('tsconfig.json'), ' file. : '
    , CLI_RED, json_last_error_msg(), PHP_EOL;
}

