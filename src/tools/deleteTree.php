<?php
declare(strict_types=1);
namespace otra\tools;

if (!function_exists('otra\\tools\\delTree'))
{
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
  function delTree(string $folder) : bool
  {
    /** @var string[] $files */
    $files = array_diff(scandir($folder), ['.','..']);

    foreach ($files as $fileName)
    {
      $filenamePath = "$folder/$fileName";

      is_dir($filenamePath)
        ? delTree($filenamePath)
        : unlink($filenamePath);
    }

    return rmdir($folder);
  }
}

