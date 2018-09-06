<?
/**
 * Class mapping generation task
 *
 * @author Lionel Péramo */
$dirs = ['bundles', 'config', 'lib'];
$classes = [];
$processedDir = 0;

define('VERBOSE', isset($argv[2]) === true ? (int) $argv[2] : 0);

foreach ($dirs as &$dir) {
  list($classes, $processedDir) = iterateCM($classes, BASE_PATH . $dir, $processedDir);
}

if (VERBOSE === 1)
  echo "\x0d\033[K", 'Processed directories : ', $processedDir, '.';

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

return null;

/**
 * @param array  $classes
 * @param string $dir
 * @param int    $processedDir
 *
 * @return array
 */
function iterateCM(array $classes, string $dir, int $processedDir)
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
          list($classes, $processedDir) = iterateCM($classes, $_entry, $processedDir);

        // Only php files are interesting
        $posDot = strrpos($entry, '.');

        if ('.php' !== substr($entry, $posDot) )
          continue;

        $content = file_get_contents(str_replace('\\', '/', realpath($_entry)));
        preg_match_all('@^\\s{0,}namespace\\s{1,}([^;{]{1,})\\s{0,}[;{]@mx', $content, $matches);

        if (isset($matches[1][0]) === true && $matches[1][0] !== '')
        {
          $classesKey = trim($matches[1][0]) . '\\' . substr($entry, 0, $posDot);

          if (isset($classes[$classesKey]) === false)
            $classes[$classesKey] // $classes[$cleanDir . '\\' . substr($entry, 0, $posDot)]
              = str_replace('\\', '/', realpath($_entry)); // we calculate the shortest string of path with realpath and str_replace function
        }
      }

      closedir($folderHandler);
      ++$processedDir;

      if (VERBOSE === 1)
        echo "\x0d\033[K", 'Processed directories : ', $processedDir, '...';

      return [$classes, $processedDir];
  }

  closedir($folderHandler);

  echo redText('Problem encountered with the directory : ' . $dir . ' !');
  exit(1);
}
