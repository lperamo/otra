<?php
declare(strict_types=1);
namespace otra\tools;
/**
 * @author Lionel Péramo
 * @package otra\tools
 */

use Exception;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\CORE_PATH;
use function otra\tools\files\returnLegiblePath;

if (!function_exists(__NAMESPACE__ . '\\cleanFileAndFolders'))
{
  /**
   * Removes all files and folders specified in the array.
   *
   * @param string[] $fileOrFolders
   *
   * @throws OtraException If we cannot remove a file or a folder
   */
  function cleanFileAndFolders(array $fileOrFolders) : void
  {
    foreach ($fileOrFolders as $folder)
    {
      if (file_exists($folder))
      {
        if (is_dir($folder))
        {
          $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
          );

          /** @var SplFileInfo $fileObject */
          foreach ($files as $fileObject)
          {
            $realPath = $fileObject->getRealPath();
            $method = $fileObject->isDir() ? 'rmdir' : 'unlink';

            if (!$method($realPath))
            {
              require CORE_PATH . 'tools/files/returnLegiblePath.php';
              throw new OtraException(
                'Cannot remove the ' .
                ($method === 'rmdir' ? 'folder ' : 'file ') . returnLegiblePath($realPath) . '.',
                E_CORE_ERROR
              );
            }
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
        } else
          unlink($folder);
      }
    }
  }
}
