<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment;

use otra\OtraException;
use function otra\src\console\deployment\googleClosureCompile;
use const otra\cache\php\{BASE_PATH,CONSOLE_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\tools\cliCommand;
use function otra\tools\files\returnLegiblePath;

const OTRA_LABEL_TSCONFIG_JSON = 'tsconfig.json';

require CONSOLE_PATH . 'deployment/googleClosureCompile.php';

/**
 * @param bool   $watching
 * @param int    $verbose
 * @param bool   $mustLaunchGcc
 * @param string $resourceFolder
 * @param string $baseName
 * @param string $resourceName
 *
 * @throws OtraException
 */
function generateJavaScript(
  bool $watching,
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
  /** @var ?array{
   *   compilerOptions?: array{
   *     target?: string,
   *     module?: string,
   *     sourceMap?: bool,
   *     lib?: string[],
   *     noResolve?: bool,
   *     pretty?: bool,
   *     removeComments?: bool,
   *     noImplicitUseStrict?: bool,
   *     watch?: bool
   *   }
   * } $typescriptConfig
   */
  $typescriptConfig = json_decode(file_get_contents(BASE_PATH . OTRA_LABEL_TSCONFIG_JSON), true);

  if ($typescriptConfig === null)
  {
    echo 'There is an error with your ', returnLegiblePath(OTRA_LABEL_TSCONFIG_JSON), ' file. : ',
      CLI_ERROR, json_last_error_msg(), PHP_EOL;

    return;
  }

  // The Google Closure Compiler application cannot overwrite a file, so we have to create a temporary one
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
  // (otherwise tsc will say that the bug is on the first line ...and the first line represents ALL the json)
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
  [, $output] = cliCommand('/usr/bin/tsc --pretty -p ' . $temporaryTypescriptConfig, null, !$watching);

  unlink($temporaryTypescriptConfig);

  $legibleCreatedTemporaryJsFile = returnLegiblePath($generatedTemporaryJsFile, '');
  $jsFileExists = file_exists($generatedTemporaryJsFile);

  if (!$jsFileExists)
  {
    echo CLI_WARNING,
      'Something was wrong during typescript compilation but this may not be blocking. Maybe you have a problem with the ',
      CLI_INFO_HIGHLIGHT, OTRA_LABEL_TSCONFIG_JSON, CLI_WARNING, ' file.', END_COLOR, PHP_EOL, $output;

    if (!$watching)
      throw new OtraException(code: 1, exit: true);

    return;
  }

  $temporarySourceMap = $generatedTemporaryJsFile . '.map';

  if ($verbose > 0)
  {
    echo CLI_BASE, 'TypeScript file ', returnLegiblePath($resourceName, ''), CLI_BASE,
    ' have generated the temporary file';

    if (file_exists($temporarySourceMap))
      echo 's ', $legibleCreatedTemporaryJsFile, CLI_BASE, ' and ',
      returnLegiblePath($generatedTemporaryJsFile . '.map', '');
    else
      echo ' ', $legibleCreatedTemporaryJsFile;

    echo CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;
  }

  // We launch Google Closure Compiler only if a file has been generated with success but...
  // should we launch Google Closure Compiler ?
  if ($mustLaunchGcc)
  {
    googleClosureCompile(
      $verbose,
      $typescriptConfig,
      $generatedTemporaryJsFile,
      $generatedJsFile,
      'ADVANCED_OPTIMIZATIONS'
    );

    if (file_exists($temporarySourceMap))
      unlink($temporarySourceMap);

    /**
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
      echo CLI_ERROR, 'Problem while moving the generated service worker file.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    $generatedJsMapFile = $generatedJsFile . '.map';
    $newJsMapPath = $serviceWorkerPath . '.map';

    if (file_exists($generatedJsMapFile) && !rename($generatedJsMapFile, $newJsMapPath))
    {
      echo CLI_ERROR, 'Problem while moving the generated service worker file mapping.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    echo 'Service worker files moved to ', CLI_INFO_HIGHLIGHT, returnLegiblePath($serviceWorkerPath),
    ' and ', CLI_INFO_HIGHLIGHT, returnLegiblePath($newJsMapPath), '.', PHP_EOL;
  }
}
