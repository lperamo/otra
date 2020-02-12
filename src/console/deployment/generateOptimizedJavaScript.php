<?php

use function src\console\returnLegiblePath;
use const src\console\GOOGLE_CLOSURE_COMPILER_VERBOSITY;

function generateJavaScript(
  bool $verbose,
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
    $jsFolder = $resourceFolder . '/js/';

    // if the js folder corresponding to the ts folder does not exist yet, we create it
    if (file_exists($jsFolder) === false)
      mkdir($jsFolder);

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

    if ($return === 0 && $jsFileExists === true)
      echo CLI_GREEN, 'TypeScript file ', returnLegiblePath($resourceName, '',
        false), CLI_GREEN, ' have generated the temporary files ', $legibleGeneratedTemporaryJsFile, ' and ',
      returnLegiblePath($generatedTemporaryJsFile . '.map', '', false), CLI_GREEN, '.',
      END_COLOR, PHP_EOL, PHP_EOL;
    else
    {
      if ($jsFileExists === true)
        echo CLI_YELLOW, 'Something was wrong during typescript compilation but this may not be blocking.',
        END_COLOR, PHP_EOL, $output, 'Launching Google Closure Compiler...', PHP_EOL;
      else
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
        // TODO add those lines to handle class map and fix the resulting issue
        // ' --create_source_map --source_map_input ' . $generatedTemporaryJsFile . '.map'
        list($return, $output) = cli('java -jar ' . CORE_PATH . 'console/compiler.jar -W '
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
        unlink($generatedTemporaryJsFile . '.map');

        /** TODO side note, the class mapping is not present when we pass by Google Closure.
         * We have to check if we can add it.
         */
      } else
      {
        rename($generatedTemporaryJsFile, $generatedJsFile);
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
  } else
    echo 'There is an error with your ', returnLegiblePath('tsconfig.json'), ' file. : '
    , CLI_RED, json_last_error_msg(), PHP_EOL;
}
?>
