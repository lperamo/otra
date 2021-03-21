<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

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
    foreach ($fileOrFolders as $folder)
    {
      if (file_exists($folder))
      {
        $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileObject)
        {
          $realPath = $fileObject->getRealPath();
          $method = $fileObject->isDir() ? 'rmdir' : 'unlink';

          if (!$method($realPath))
            throw new OtraException('Cannot remove the file/folder \'' . $realPath . '\'.', E_CORE_ERROR);
        }

        $exceptionMessage = 'Cannot remove the folder \'' . $folder . '\'.';

        try
        {
          if (!rmdir($folder))
            throw new OtraException($exceptionMessage, E_CORE_ERROR);
        } catch (Exception $exception)
        {
          throw new OtraException(
            'Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ' .
              $exceptionMessage,
            $exception->getCode()
          );
        }
      }
    }
  }
}

