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
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH, DEV, DIR_SEPARATOR, PROD};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\console\shortenVarExportArray;

/**
 * @param string $environment DEV or PROD
 */
function generateClassMap(string $classMap, string $filename, string $environment = DEV) : void
{
  $filePointer = fopen(CACHE_PHP_INIT_PATH . $filename, 'w');
  $contentToWrite = convertClassMapToPHPFile($classMap, $environment) . PHP_EOL;
  fwrite($filePointer, $contentToWrite, strlen($contentToWrite));
  fclose($filePointer);
}

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException
 * @return void
 */
function genClassMap(array $argumentsVector) : void
{
  if (!defined(__NAMESPACE__ . '\\VERBOSE'))
  {
    define(__NAMESPACE__ . '\\VERBOSE', isset($argumentsVector[2]) ? (int) $argumentsVector[2] : 0);
    define(__NAMESPACE__ . '\\ADDITIONAL_CLASSES_FILES_PATH', BASE_PATH . 'config/AdditionalClassFiles.php');
    define(__NAMESPACE__ . '\\SOURCE_FOLDER', 'src');
  }

  $folders = [
    BASE_PATH . 'bundles',
    BASE_PATH . 'config',
    BASE_PATH . SOURCE_FOLDER,
    BASE_PATH . 'tests',
    BASE_PATH . 'vendor'
  ];
  $classes = [];
  $processedDir = 0;

  $additionalClassesFiles = [];

  if (file_exists(ADDITIONAL_CLASSES_FILES_PATH))
    $additionalClassesFiles = require ADDITIONAL_CLASSES_FILES_PATH;

  $additionalClassesFilesKeys = array_keys($additionalClassesFiles);
  $classesThatMayHaveToBeAdded = [];

  // Condition mandatory if we do not launch genClassMap directly
  if (!function_exists('otra\console\promptUser'))
    require CONSOLE_PATH . 'tools.php';

  if (!empty($folders) && !function_exists(__NAMESPACE__ . '\\iterateCM'))
  {
    /**
     * @param string[]              $classes
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
      string $folder,
      array $additionalClassesFilesKeys,
      int &$processedFolder,
      array &$classesThatMayHaveToBeAdded) : array
    {
      $folderHandler = opendir($folder);

      if (!$folderHandler)
      {
        echo CLI_ERROR, 'Problem encountered with the directory : ' . $folder . ' !', END_COLOR;
        throw new OtraException(code: 1, exit: true);
      }

      while (false !== ($entry = readdir($folderHandler)))
      {
        // We check that we process interesting things, especially not starters that are only meant to be copied
        if ('.' === $entry
          || '..' === $entry
          || str_contains($entry, 'phpstan')
          || str_contains($entry, 'rector')
          || str_contains($entry, 'starters')
        )
          continue;

        $entryAbsolutePath = $folder . DIR_SEPARATOR . $entry;

        // recursively...
        if (is_dir($entryAbsolutePath))
          [$classes, $processedFolder] = iterateCM(
            $classes,
            $entryAbsolutePath,
            $additionalClassesFilesKeys,
            $processedFolder,
            $classesThatMayHaveToBeAdded
          );

        // Only php files that begin by an uppercase letter are interesting (as classes MUST begin by an uppercase letter)
        $posDot = strrpos($entry, '.');

        if ($posDot === false
          || '.php' !== substr($entry, $posDot)
          || !ctype_upper($entry[0]))
          continue;

        if (in_array(
          $entryAbsolutePath,
          [
            BASE_PATH . 'config/dev/AllConfig.php',
            BASE_PATH . 'config/prod/AllConfig.php'
          ]))
          continue;

        $revisedEntryAbsolutePath = str_replace('\\', DIR_SEPARATOR, realpath($entryAbsolutePath));
        $content = file_get_contents($revisedEntryAbsolutePath);
        preg_match_all('@^\\s*namespace\\s+([^;{]+)\\s*[;{]@mx', $content, $matches);

        if (!isset($matches[1]))
          continue;

        // we calculate the shortest string of path with realpath and str_replace function
        $className = substr($entry, 0, $posDot);

        foreach($matches[1] as $namespace)
        {
          $classNamespace = trim($namespace) . '\\' . $className;

          if (!isset($classes[$classNamespace]))
            $classes[$classNamespace] = $revisedEntryAbsolutePath;
          elseif (!in_array($classNamespace, $additionalClassesFilesKeys))
            $classesThatMayHaveToBeAdded[$classNamespace] = str_replace(
              BASE_PATH,
              '',
              $revisedEntryAbsolutePath
            );
          else
            $classes[$classNamespace] = $revisedEntryAbsolutePath;
        }

      }

      closedir($folderHandler);
      ++$processedFolder;

      if (VERBOSE === 1)
        echo "\x0d\033[K", 'Processed directories : ', $processedFolder, '...';

      return [$classes, $processedFolder, $classesThatMayHaveToBeAdded];
    }

    /**
     * Strips spaces, use the short array notation [] and changes \\\\ by \\.
     * We take care of the spaces contained into folders and files names.
     * We also reduce paths using constants.
     *
     * @return string
     */
    function convertClassMapToPHPFile(string $classMap, string $environment = DEV) : string
    {
      $start = '<?php declare(strict_types=1);namespace otra\\cache\\php' .
        ($environment === DEV
        ? '\\init;use const otra\\cache\\php\\{BASE_PATH,CONSOLE_PATH,'
        : ';use const otra\\cache\\php\\{');

      // if the class map is empty, then we just return an empty array.
      return $start . 'CORE_PATH};const CLASSMAP=' .
        (($classMap === 'array (' . PHP_EOL . ')')
          ? '[];'
          : shortenVarExportArray(str_replace(
            [
              '\'' . CONSOLE_PATH,
              '\'' . CORE_PATH,
              '\'' . BASE_PATH
            ],
            [
              'CONSOLE_PATH.\'',
              'CORE_PATH.\'',
              'BASE_PATH.\''
            ],
            $classMap
          ))
        ) . ';';
    }
  }

  foreach ($folders as $folder)
  {
    // if the user wants to launch tasks in an empty project when there are not a class map yet,
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
    echo "\x0d\033[K", 'Processed directories : ', $processedDir, '.', PHP_EOL;

  $classes = array_merge($classes, $additionalClassesFiles);

  // Calculate "production" classes.
  // Classes from the framework will be integrated in the bootstraps, so they do not need to be in the final class map
  $prodClasses = [];

  $basePathLength = mb_strlen(BASE_PATH);

  /**
   * Loop through all classes to filter the ones needed for production.
   *
   * @var string $classNamespace
   * @var string  $class
   */
  foreach($classes as $classNamespace => $class)
  {
    // We only let external libraries
    if (str_contains($class, BASE_PATH))
    {
      // Get the relative path of the class from the BASE_PATH.
      $tmpClass = mb_substr($class, $basePathLength);

      // // Find the first directory after BASE_PATH in the class's path.
      $firstFolderAfterBasePath = mb_substr($tmpClass, 0, mb_strpos($tmpClass, '/'));

      // Check if the folder matches SOURCE_FOLDER or 'web', while making sure
      // it's not a subset of SOURCE_FOLDER.
      // Also, specifically exclude the DumpMaster class since it's dynamically loaded
      // and therefore not integrated into the final bootstrap.
      if (
        (!($firstFolderAfterBasePath === SOURCE_FOLDER || $firstFolderAfterBasePath === 'web')
          || str_contains($tmpClass, SOURCE_FOLDER))
        && !(str_contains($tmpClass, 'DumpMaster'))
      )
        continue;
    }

    // If all the conditions above are met, the class is deemed a production class.
    $prodClasses[$classNamespace]= $class;
  }

  $classMap = var_export($classes, true);
  $prodClassMap = var_export($prodClasses, true);

  if (!file_exists(CACHE_PHP_INIT_PATH))
    mkdir(CACHE_PHP_INIT_PATH, 0755, true);

  // Forced to use fopen/fwrite + specified length otherwise PHP_EOL is automatically trimmed !!!
  // Generating class maps
  generateClassMap($classMap, 'ClassMap.php');
  generateClassMap($prodClassMap, 'ProdClassMap.php', PROD);

  echo 'Class mapping finished', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  // If we want verbose output, then we display the files found related to the classes
  if (VERBOSE !== 1)
    return;

  // If we come from the `deploy` task, this constant may already have been defined.
  if (!defined(__NAMESPACE__ . '\\FIRST_CLASS_PADDING'))
    define(__NAMESPACE__ . '\\FIRST_CLASS_PADDING', 80);

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

  /** Shows a help to find classes that may have to be added to the custom configuration in order to complete
   *  this automatic task */
  if (!empty($classesThatMayHaveToBeAdded))
  {
    echo PHP_EOL, 'You may have to add these classes in order to make your project work.', PHP_EOL,
    'Maybe because you use dynamic class inclusion via require(_once)/include(_once) statements.', PHP_EOL, PHP_EOL;

    foreach($classesThatMayHaveToBeAdded as $namespace => $classFile)
    {
      echo str_pad('Class ' . CLI_WARNING . $namespace . END_COLOR . ' ', FIRST_CLASS_PADDING,
        '.'), ' => possibly related file ', CLI_WARNING, $classFile, END_COLOR, PHP_EOL;
    }
  }
}
