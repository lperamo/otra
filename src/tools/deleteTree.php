<?php
declare(strict_types=1);

/**
 * Deletes a tree recursively.
 *
 * @param string $dir
 *
 * @return bool
 */
$delTree = function (string $dir) use (&$delTree) : bool
{
  $files = array_diff(scandir($dir), ['.','..']);

  foreach ($files as $file)
  {
    (is_dir("$dir/$file") === true)
      ? $delTree("$dir/$file")
      : unlink("$dir/$file");
  }

  return rmdir($dir);
};
