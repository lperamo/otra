<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * Deletes a tree recursively.
 *
 * @param string $folder
 *
 * @return bool
 */
$delTree = function (string $folder) use (&$delTree) : bool
{
  $files = array_diff(scandir($folder), ['.','..']);

  foreach ($files as $fileName)
  {
    (is_dir("$folder/$fileName"))
      ? $delTree("$folder/$fileName")
      : unlink("$folder/$fileName");
  }

  return rmdir($folder);
};
