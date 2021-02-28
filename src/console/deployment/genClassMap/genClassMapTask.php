<?php
declare(strict_types=1);

/**
 * Class mapping generation task
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
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
   * @param string[] $classes
   * @param string   $dir
   * @param array    $additionalClassesFilesKeys
   * @param int      $processedDir
   * @param array    $classesThatMayHaveToBeAdded
   *
   * @throws \otra\OtraException
   * @return array
   */
  #[\JetBrains\PhpStorm\ArrayShape([
    'array',
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
        if (str_contains($entryAbsolutePath, BASE_PATH . 'config/dev') && $_SERVER[APP_ENV] === 'prod')
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

    echo CLI_RED, 'Problem encountered with the directory : ' . $dir . ' !', END_COLOR;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
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
      return '<?php define(\'CLASSMAP\', []);';

    $withBasePathStripped = str_replace('\'' . CORE_PATH, 'CORE_PATH.\'', $classMap);
    $withBasePathStripped = str_replace('\'' . BASE_PATH, 'BASE_PATH.\'', $withBasePathStripped);

    return '<?php define(\'CLASSMAP\',' . convertArrayFromVarExportToShortVersion($withBasePathStripped) . ');';
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
  echo "\x0d\033[K", 'Processed directories : ', $processedDir, '.';

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
  if (mb_strpos($class, BASE_PATH) !== false)
  {
    $tmpClass = mb_substr($class, mb_strlen(BASE_PATH));
    $firstFolderAfterBasePath = mb_substr($tmpClass, 0, mb_strpos($tmpClass, '/'));

    if (
      (in_array($firstFolderAfterBasePath, ['src', 'web']) && mb_strpos($tmpClass, 'src') === false)
      // temporary fix for DumpMaster class as it is not integrated in the final bootstrap because this class is
      // dynamically loaded
      || (mb_strpos($tmpClass, 'DumpMaster') !== false))
      $prodClasses[$classNamespace] = $class;
  } else
    $prodClasses[$classNamespace]= $class;
}

$classMap = var_export($classes, true);
$prodClassMap = var_export($prodClasses, true);
$classMapPath = BASE_PATH . 'cache/php/';

if (!file_exists($classMapPath))
  mkdir($classMapPath, 0755, true);

// Forced to use fopen/fwrite + specified length otherwise PHP_EOL is automatically trimmed !!!
// Generating development class map
$filePointer = fopen($classMapPath . 'ClassMap.php', 'w');
$contentToWrite = convertClassMapToPHPFile($classMap) . PHP_EOL;
fwrite($filePointer, $contentToWrite, strlen($contentToWrite));
fclose($filePointer);

// Generating production class map
$filePointer = fopen($classMapPath . 'ProdClassMap.php', 'w');
$contentToWrite = convertClassMapToPHPFile($prodClassMap) . PHP_EOL;
fwrite($filePointer, $contentToWrite, strlen($contentToWrite));
fclose($filePointer);

echo CLI_LIGHT_GREEN, ' Class mapping finished.', END_COLOR, PHP_EOL, PHP_EOL;

// If we want verbose output, then we display the files found related to the classes
if (VERBOSE !== 1)
  return;

// If we come from the deploy task, this constant may already have been defined.
if (!defined('FIRST_CLASS_PADDING'))
  define('FIRST_CLASS_PADDING', 80);

echo CLI_YELLOW, 'BASE_PATH = ', BASE_PATH, PHP_EOL;
echo CLI_LIGHT_BLUE, 'Class path', CLI_GREEN, ' => ', CLI_LIGHT_BLUE, 'Related file path', PHP_EOL, PHP_EOL;

foreach($classes as $startClassName => $finalClassName)
{
  echo CLI_LIGHT_BLUE, str_pad($startClassName, FIRST_CLASS_PADDING, '.'), CLI_GREEN, ' => ';
  echo (str_contains($finalClassName, BASE_PATH)
    // for classes inside the BASE_PATH
    ? CLI_WHITE . '[BASE_PATH]' . CLI_LIGHT_BLUE . substr($finalClassName, strlen(BASE_PATH))
    // for classes outside the BASE_PATH
    : CLI_LIGHT_BLUE . $finalClassName),
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

  /**
   * @var string $namespace
   * @var string $classFile
   */
  foreach($classesThatMayHaveToBeAdded as $namespace => $classFile)
  {
    echo str_pad('Class ' . CLI_YELLOW . $namespace . END_COLOR, FIRST_CLASS_PADDING,
      '.'), '=> possibly related file ', CLI_YELLOW, $classFile, END_COLOR, PHP_EOL;
  }
}

return;
