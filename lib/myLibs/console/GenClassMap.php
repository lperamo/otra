<?
/**
 * Class mapping generation task
 *
 * @author Lionel Péramo */
$dirs = ['bundles', 'config', 'lib'];
$classes = [];
$processedDir = 0;

if (defined('VERBOSE') === false)
  define('VERBOSE', isset($argv[2]) === true ? (int) $argv[2] : 0);

$additionalClassesFilesPath = BASE_PATH . 'config/AdditionalClassFiles.php';
$additionalClassesFiles = [];

if (file_exists($additionalClassesFilesPath) === true)
  $additionalClassesFiles = require $additionalClassesFilesPath;

$additionalClassesFilesKeys = array_keys($additionalClassesFiles);
$classesThatMayHaveToBeAdded = [];

if (empty($dirs) === false && function_exists('iterateCM') === false)
{
  /**
   * @param array  $classes
   * @param string $dir
   * @param array  $additionalClassesFilesKeys
   * @param int    $processedDir
   * @param array  $classesThatMayHaveToBeAdded
   *
   * @return array
   */
  function iterateCM(array &$classes, string $dir, array &$additionalClassesFilesKeys, int &$processedDir, &$classesThatMayHaveToBeAdded)
  {
    if ($folderHandler = opendir($dir))
    {
      while (false !== ($entry = readdir($folderHandler)))
      {
        // We check that we process interesting things
        if ('.' === $entry || '..' === $entry)
          continue;

        $_entry = $dir . '/' . $entry;

        // recursively...
        if (is_dir($_entry) === true)
          list($classes, $processedDir) = iterateCM($classes, $_entry, $additionalClassesFilesKeys, $processedDir, $classesThatMayHaveToBeAdded);

        // Only php files are interesting
        $posDot = strrpos($entry, '.');

        if ('.php' !== substr($entry, $posDot))
          continue;

        $content = file_get_contents(str_replace('\\', '/', realpath($_entry)));
        preg_match_all('@^\\s{0,}namespace\\s{1,}([^;{]{1,})\\s{0,}[;{]@mx', $content, $matches);

        // we calculate the shortest string of path with realpath and str_replace function
        $fullFilePath = str_replace('\\', '/', realpath($_entry));
        $className    = substr($entry, 0, $posDot);

        if (isset($matches[1][0]) === true && $matches[1][0] !== '')
        {
          // We put the namespace into $classesKey
          $classesKey = trim($matches[1][0]) . '\\' . $className;

          if (isset($classes[$classesKey]) === false)
            $classes[$classesKey] = $fullFilePath;
          else if (in_array($classesKey, $additionalClassesFilesKeys) === false)
            $classesThatMayHaveToBeAdded[$classesKey] = str_replace(BASE_PATH, '', $fullFilePath);
        }
      }

      closedir($folderHandler);
      ++$processedDir;

      if (VERBOSE === 1)
        echo "\x0d\033[K", 'Processed directories : ', $processedDir, '...';

      return [$classes, $processedDir, $classesThatMayHaveToBeAdded];
    }

    closedir($folderHandler);

    echo redText('Problem encountered with the directory : ' . $dir . ' !');
    exit(1);
  }
}

foreach ($dirs as &$dir) {
  list($classes, $processedDir, $classesThatMayHaveToBeAdded) = iterateCM($classes, BASE_PATH . $dir, $additionalClassesFilesKeys, $processedDir, $classesThatMayHaveToBeAdded);
}

if (VERBOSE === 1)
  echo "\x0d\033[K", 'Processed directories : ', $processedDir, '.';

$classes = array_merge($classes, $additionalClassesFiles);

$classMap = var_export($classes, true);
$classMapPath = BASE_PATH . 'cache/php/';

if (file_exists($classMapPath) === false)
  mkdir($classMapPath, 0755, true);

// We strip spaces, PHP7'izes the content and changes \\\\ by \\ ...before saving the file.
// Here, we take care of the spaces contained into folders and files names.
file_put_contents(
  $classMapPath . 'ClassMap.php',
  '<? define(\'CLASSMAP\',' . substr(
    str_replace(
      ['\\\\', ' => ', '  \'', "\n", 'array ('],
      ['\\', '=>', '\'', '', '['],
      $classMap
    ),
    0,
    -2
  ) . ']);?>'
);

echo lightGreen() , ' Class mapping finished.', endColor(), PHP_EOL, PHP_EOL;

// If we want verbose output, then we display the files found related to the classes
if (VERBOSE !== 1)
  return;

define('FIRST_CLASS_PADDING', 80);
echo brown(), 'BASE_PATH = ', BASE_PATH, PHP_EOL;
echo lightBlue(), 'Class path', green(), ' => ', lightBlue(), 'Related file path', PHP_EOL, PHP_EOL;

foreach($classes as $startClassName => &$finalClassName)
{
  echo lightBlue(), str_pad($startClassName, FIRST_CLASS_PADDING, '.'), green(), ' => ';
  echo (strpos($finalClassName, BASE_PATH) !== false
    // for classes inside the BASE_PATH
    ? white() . '[BASE_PATH]' . lightBlue() . substr($finalClassName, strlen(BASE_PATH))
    // for classes outside the BASE_PATH
    : lightBlue() . $finalClassName),
    // and we pass to the next line !
    PHP_EOL;
}

echo endColor();

/** Shows an help to find classes that may have to be added to the custom configuration in order to complete
 *  this automatic task */
if (empty($classesThatMayHaveToBeAdded) === false)
{
  echo PHP_EOL, 'You may have to add these classes in order to make your project work.', PHP_EOL,
  'Maybe because you use dynamic class inclusion via require(_once)/include(_once) statements.', PHP_EOL, PHP_EOL;

  foreach($classesThatMayHaveToBeAdded as $key => &$namespace)
  {
    echo str_pad('Class ' . brownText($key), FIRST_CLASS_PADDING, '.'), '=> possibly related file ', brownText($namespace), PHP_EOL;
  }
}

return null;