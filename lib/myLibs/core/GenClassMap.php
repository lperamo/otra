<?
/** Class mapping generation task
 *
 * @author Lionel Péramo */
$dirs = array('bundles', 'config', 'lib');
$classes = array();
$processedDir = 0;
foreach ($dirs as $dir){
  list($classes, $processedDir) = iterateCM($classes, ROOTPATH . $dir, $processedDir);
}
ob_start();
var_export($classes);
$classMap = ob_get_clean();
$fp = fopen(ROOTPATH . 'lib/myLibs/core/ClassMap.php', 'w');
fwrite($fp, '<?php $classMap = ' . substr(str_replace(array('\\\\', ' ', "\n"), array('\\', '', ''), $classMap), 0, -2) . ');');
fclose($fp);

echo PHP_EOL, green() , 'Class mapping finished.', endColor(), PHP_EOL;

function iterateCM($classes, $dir, $processedDir)
{
  if ($handle = opendir($dir)) {
      while (false !== ($entry = readdir($handle))) {
        // We check that we process interesting things
        if('.' == $entry || '..' == $entry)
          continue;

        $_entry = $dir . DS . $entry;

        // recursively...
        if(is_dir($_entry))
          list($classes, $processedDir) = iterateCM($classes, $_entry, $processedDir);

        // Only php files are interesting
        $posDot = strrpos($entry, ".");
        if('.php' != (substr($entry, $posDot) ))
          continue;

        $classes[substr(str_replace('/', '\\', $dir), strlen(ROOTPATH)) . '\\' . substr($entry, 0, $posDot)] = $_entry;
      }
      closedir($handle);
      $processedDir += 1;
      echo "\x0d\033[K", 'Processed directories : ', $processedDir, '...';

      return array($classes, $processedDir);
  }

  die ('Problem encountered with the directory : ' . $dir . ' !');
}
