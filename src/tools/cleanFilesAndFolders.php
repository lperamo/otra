<?php
declare(strict_types=1);

use otra\OtraException;

if (!function_exists('cleanFileAndFolders'))
{
  /**
   * Removes all files and folders specified in the array.
   *
   * @param array $fileOrFolders
   *
   * @throws OtraException If we cannot remove a file or a folder
   */
  function cleanFileAndFolders(array $fileOrFolders) : void
  {
    foreach ($fileOrFolders as &$folder)
    {
      if (true === file_exists($folder))
      {
        $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file)
        {
          $realPath = $file->getRealPath();
          $method = true === $file->isDir() ? 'rmdir' : 'unlink';

          if (false === $method($realPath))
            throw new OtraException('Cannot remove the file/folder \'' . $realPath . '\'.', E_CORE_ERROR);
        }

        $exceptionMessage = 'Cannot remove the folder \'' . $folder . '\'.';

        try
        {
          if (false === rmdir($folder))
            throw new OtraException($exceptionMessage, E_CORE_ERROR);
        } catch (Exception $e)
        {
          throw new OtraException('Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ' . $exceptionMessage, $e->getCode());
        }
      }
    }
  }
}

