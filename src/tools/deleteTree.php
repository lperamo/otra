<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 *
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
    $filenamePath = "$folder/$fileName";

    is_dir($filenamePath)
      ? $delTree($filenamePath)
      : unlink($filenamePath);
  }

  return rmdir($folder);
};
