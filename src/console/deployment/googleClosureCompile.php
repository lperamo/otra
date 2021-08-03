<?php
declare(strict_types=1);
namespace otra\src\console\deployment;

use otra\OtraException;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_SUCCESS, END_COLOR};

use function otra\tools\cliCommand;
use function otra\tools\files\returnLegiblePath;

const
  JAVA_COMPILING_PATH = 'java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar "' . CONSOLE_PATH .
  'deployment/compiler.jar"',
  PROBLEM_OCCURRED = CLI_ERROR . 'A problem occurred.' . END_COLOR,
  GOOGLE_CLOSURE_COMPILER_VERBOSITY = ['QUIET', 'DEFAULT', 'VERBOSE'];

/**
 * @param int    $verbose
 * @param array  $typescriptConfig
 * @param string $jsFileIn
 * @param string $jsFileOut
 * @param string $compilationLevel
 *
 * @throws OtraException
 */
function googleClosureCompile(
  int $verbose,
  array $typescriptConfig,
  string $jsFileIn,
  string $jsFileOut,
  string $compilationLevel
)
{
  if ($verbose > 0)
    echo 'Launching Google Closure Compiler...', PHP_EOL;

  $jsLanguageTarget = match ($typescriptConfig['compilerOptions']['target'])
  {
    'ES3' => 'ECMASCRIPT3',
    'ES5' => 'ECMASCRIPT5',
    'ES2015' => 'ECMASCRIPT_2015',
    'ES2016' => 'ECMASCRIPT_2016',
    'ES2017' => 'ECMASCRIPT_2017',
    'ES2018' => 'ECMASCRIPT_2018',
    'ES2019' => 'ECMASCRIPT_2019',
    'ES2020' => 'ECMASCRIPT_2020',
    'ESNext' => 'ECMASCRIPT_NEXT',
    default  => 'ECMASCRIPT_NEXT'
  };

  // ' --create_source_map --source_map_input ' . $jsFileIn . '.map'
  // We do not launch an exception on error to avoid stopping the execution of the watcher
  $compilerOptions = ' --logging_level FINEST -W ' . GOOGLE_CLOSURE_COMPILER_VERBOSITY[$verbose] .
    ' --rewrite_polyfills=false --language_in ' . $jsLanguageTarget . ' --language_out ' .
    $jsLanguageTarget . ' --js ' . $jsFileIn . ' --js_output_file ' . $jsFileOut . ' -O ' . $compilationLevel;

  $jamVmCommand = 'jamvm -Xmx32m -jar ' . '../src/yuicompressor-2.4.8.jar' . $compilerOptions;
  $linux = !str_contains(php_uname('s'), 'Windows');

  // Linux or Windows ? We have java or jamvm ?
  if (($linux && !empty(exec('which java'))) // LINUX JAVA CASE
    || (!$linux && !empty(exec('where java')))) // WINDOWS JAVA CASE
    [, $output] = cliCommand(
      JAVA_COMPILING_PATH . $compilerOptions,
      PROBLEM_OCCURRED,
      false
    );
  else // JAMVM
    [, $output] = cliCommand($jamVmCommand, PROBLEM_OCCURRED, false);

  if ($verbose > 0)
    echo $output, CLI_BASE, 'Javascript ', returnLegiblePath($jsFileOut), ' has been optimized', CLI_SUCCESS,
      ' ✔', PHP_EOL;

  // Cleaning temporary files ...(js and the mapping)
  unlink($jsFileIn);
}
