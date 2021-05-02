<?php
/**
 * Class mapping generation task
 *
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genClassMap;

use JetBrains\PhpStorm\ArrayShape;
use otra\OtraException;
use function otra\console\convertArrayFromVarExportToShortVersion;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use const otra\bin\CACHE_PHP_INIT_PATH;

$folders = [
  BASE_PATH . 'bundles',
  BASE_PATH . 'config',
  BASE_PATH . 'src',
  BASE_PATH . 'vendor'
];
$classes = [];
$processedDir = 0;

if (!defined('VERBOSE'))
  define('VERBOSE', isset($argv[2]) ? (int) $argv[2] : 0);

$additionalClassesFilesPath = BASE_PATH . 'config/AdditionalClassFiles.php';
$additionalClassesFiles = [];

if (file_exists($additionalClassesFilesPath))
  $additionalClassesFiles = require $additionalClassesFilesPath;

$additionalClassesFilesKeys = array_keys($additionalClassesFiles);
$classesThatMayHaveToBeAdded = [];

require CONSOLE_PATH . 'tools.php';

if (!empty($folders) && !function_exists('iterateCM'))
{
  /**
   * @param string[]              $classes
   * @param string                $dir
   * @param array                 $additionalClassesFilesKeys
   * @param int                   $processedDir
   * @param array<string, string> $classesThatMayHaveToBeAdded
   *
   * @throws OtraException
   * @return array{0: string[], 1: int, 2: array<string, string>}
   */
  #[ArrayShape([
    'string[]',
    'int',
    'array'
  ])]
  function iterateCM(
    array &$classes,
    string $dir,
    array &$additionalClassesFilesKeys,
    int &$processedDir,
    array &$classesThatMayHaveToBeAdded) : array
  {
    if ($folderHandler = opendir($dir))
    {
      while (false !== ($entry = readdir($folderHandler)))
      {
        // We check that we process interesting things
        if ('.' === $entry || '..' === $entry)
          continue;

        $entryAbsolutePath = $dir . '/' . $entry;

        // recursively...
        if (is_dir($entryAbsolutePath))
          [$classes, $processedDir] = iterateCM(
            $classes,
            $entryAbsolutePath,
            $additionalClassesFilesKeys,
            $processedDir,
            $classesThatMayHaveToBeAdded
          );

        // Only php files are interesting
        $posDot = strrpos($entry, '.');

        if ($posDot === false || '.php' !== substr($entry, $posDot))
          continue;

        // We only need files that match with the actual environment
        // so, for example, we'll not include dev config if we are in prod mode !
        if (str_contains($entryAbsolutePath, BASE_PATH . 'config/dev') && $_SERVER[APP_ENV] === PROD)
          continue;

        $content = file_get_contents(str_replace('\\', '/', realpath($entryAbsolutePath)));
        preg_match_all('@^\\s{0,}namespace\\s{1,}([^;{]{1,})\\s{0,}[;{]@mx', $content, $matches);

        // we calculate the shortest string of path with realpath and str_replace function
        $revisedEntryAbsolutePath = str_replace('\\', '/', realpath($entryAbsolutePath));
        $className    = substr($entry, 0, $posDot);

        if (isset($matches[1][0]) && $matches[1][0] !== '')
        {
          $classNamespace = trim($matches[1][0]) . '\\' . $className;

          if (!isset($classes[$classNamespace]))
            $classes[$classNamespace] = $revisedEntryAbsolutePath;
          elseif (!in_array($classNamespace, $additionalClassesFilesKeys))
            $classesThatMayHaveToBeAdded[$classNamespace] = str_replace(BASE_PATH, '', $revisedEntryAbsolutePath);
          else
            $classes[$classNamespace] = $revisedEntryAbsolutePath;
        }
      }

      closedir($folderHandler);
      ++$processedDir;

      if (VERBOSE === 1)
        echo "\x0d\033[K", 'Processed directories : ', $processedDir, '...';

      return [$classes, $processedDir, $classesThatMayHaveToBeAdded];
    }

    closedir($folderHandler);

    echo CLI_ERROR, 'Problem encountered with the directory : ' . $dir . ' !', END_COLOR;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  /**
   * Strips spaces, use the short array notation [] and changes \\\\ by \\.
   * We take care of the spaces contained into folders and files names.
   * We also reduce paths using constants.
   *
   * @param string $classMap
   *
   * @return string
   */
  function convertClassMapToPHPFile(string $classMap) : string
  {
    // if the class map is empty, then we just return an empty array.
    if ($classMap === 'array (' . PHP_EOL . ')')
      return '<?php declare(strict_types=1);namespace otra\cache\php\init;' .
        'use const \otra\config\{BASE_PATH,CORE_PATH};const CLASSMAP=[];';

    $withBasePathStripped = str_replace('\'' . CORE_PATH, 'CORE_PATH.\'', $classMap);
    $withBasePathStripped = str_replace('\'' . BASE_PATH, 'BASE_PATH.\'', $withBasePathStripped);

    return '<?php declare(strict_types=1);namespace otra\cache\php\init;use const \otra\config\{BASE_PATH,CORE_PATH};'.
      'const CLASSMAP=' . convertArrayFromVarExportToShortVersion($withBasePathStripped) . ';';
  }
}

foreach ($folders as $folder)
{
  // if the user wants to launch tasks in an empty project when there are not a class map yet
  // we need to check if the needed folders exist
  if (!file_exists($folder))
    mkdir($folder);

  [$classes, $processedDir, $classesThatMayHaveToBeAdded] = iterateCM(
    $classes,
    $folder,
    $additionalClassesFilesKeys,
    $processedDir,
    $classesThatMayHaveToBeAdded
  );
}

if (VERBOSE === 1)
  echo "\x0d\033[K", 'Processed directories : ', $processedDir, '. ';

$classes = array_merge($classes, $additionalClassesFiles);

// Calculate "production" classes
// classes from the framework will be integrated in the bootstraps so they do not need to be in the final class map
$prodClasses = [];

/**
 * @var string $classNamespace
 * @var string  $class
 */
foreach($classes as $classNamespace => $class)
{
  // We only let external libraries
  if (str_contains($class, BASE_PATH))
  {
    $tmpClass = mb_substr($class, mb_strlen(BASE_PATH));
    $firstFolderAfterBasePath = mb_substr($tmpClass, 0, mb_strpos($tmpClass, '/'));

    if (
      (in_array($firstFolderAfterBasePath, ['src', 'web']) && !str_contains($tmpClass, 'src'))
      // temporary fix for DumpMaster class as it is not integrated in the final bootstrap because this class is
      // dynamically loaded
      || (str_contains($tmpClass, 'DumpMaster')))
      $prodClasses[$classNamespace] = $class;
  } else
    $prodClasses[$classNamespace]= $class;
}

$classMap = var_export($classes, true);
$prodClassMap = var_export($prodClasses, true);
const CACHE_PHP_PATH = BASE_PATH . 'cache/php/';

if (!file_exists(CACHE_PHP_INIT_PATH))
  mkdir(CACHE_PHP_INIT_PATH, 0755, true);

// Forced to use fopen/fwrite + specified length otherwise PHP_EOL is automatically trimmed !!!
// Generating development class map
$filePointer = fopen(CACHE_PHP_INIT_PATH . 'ClassMap.php', 'w');
$contentToWrite = convertClassMapToPHPFile($classMap) . PHP_EOL;
fwrite($filePointer, $contentToWrite, strlen($contentToWrite));
fclose($filePointer);

// Generating production class map
$filePointer = fopen(CACHE_PHP_INIT_PATH . 'ProdClassMap.php', 'w');
$contentToWrite = convertClassMapToPHPFile($prodClassMap) . PHP_EOL;
fwrite($filePointer, $contentToWrite, strlen($contentToWrite));
fclose($filePointer);

echo 'Class mapping finished', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;

// If we want verbose output, then we display the files found related to the classes
if (VERBOSE !== 1)
  return;

// If we come from the deploy task, this constant may already have been defined.
if (!defined('FIRST_CLASS_PADDING'))
  define('FIRST_CLASS_PADDING', 80);

echo CLI_WARNING, 'BASE_PATH = ', BASE_PATH, PHP_EOL;
echo CLI_INFO, 'Class path', CLI_INFO_HIGHLIGHT, ' => ', CLI_INFO, 'Related file path', PHP_EOL, PHP_EOL;

foreach($classes as $startClassName => $finalClassName)
{
  echo CLI_INFO, str_pad($startClassName, FIRST_CLASS_PADDING, '.'), CLI_INFO_HIGHLIGHT, ' => ';
  echo (str_contains($finalClassName, BASE_PATH)
    // for classes inside the BASE_PATH
    ? CLI_BASE . '[BASE_PATH]' . CLI_INFO . substr($finalClassName, strlen(BASE_PATH))
    // for classes outside the BASE_PATH
    : CLI_INFO . $finalClassName),
    // and we pass to the next line !
    PHP_EOL;
}

echo END_COLOR;

/** Shows an help to find classes that may have to be added to the custom configuration in order to complete
 *  this automatic task */
if (!empty($classesThatMayHaveToBeAdded))
{
  echo PHP_EOL, 'You may have to add these classes in order to make your project work.', PHP_EOL,
  'Maybe because you use dynamic class inclusion via require(_once)/include(_once) statements.', PHP_EOL, PHP_EOL;

  foreach($classesThatMayHaveToBeAdded as $namespace => $classFile)
  {
    echo str_pad('Class ' . CLI_WARNING . $namespace . END_COLOR, FIRST_CLASS_PADDING,
      '.'), '=> possibly related file ', CLI_WARNING, $classFile, END_COLOR, PHP_EOL;
  }
}

return;
