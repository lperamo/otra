<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

use otra\OtraException;

if (!function_exists('copyFileAndFolders'))
{
  /**
   * Copy the file or an entire folder to the destination
   *
   * @param string[] $filesOrFoldersSrc  Must be the absolute path
   * @param string[] $filesOrFoldersDest Must be the absolute path
   *
   * @throws OtraException If we can't create a folder or copy a file.
   */
  function copyFileAndFolders(array $filesOrFoldersSrc, array $filesOrFoldersDest) : void
  {
    /** @var int $key */
    foreach ($filesOrFoldersSrc as $numericKey => $fileOrFolderSrc)
    {
      $fileOrFolderDest = $filesOrFoldersDest[$numericKey];

      if (is_dir($fileOrFolderSrc))
        iterateOnFilesAndFolders($fileOrFolderSrc, $fileOrFolderDest);
      else
      {
        $destinationFolder = substr($fileOrFolderDest, 0, -strlen(basename($fileOrFolderDest)));

        if (!file_exists($destinationFolder))
          mkdir($destinationFolder, 0777, true);

        if (!copy($fileOrFolderSrc, $fileOrFolderDest))
          throw new OtraException(
            'Cannot copy the file \'' . $fileOrFolderSrc . ' to ' . $fileOrFolderDest . '\'.',
            E_CORE_ERROR
          );
      }
    }
  }

  /**
   * @param string $source
   * @param string $destination
   *
   * @throws OtraException
   */
  function iterateOnFilesAndFolders(string $source, string $destination): void
  {
    if (!file_exists($destination) && !mkdir($destination, 0777, true))
      throw new OtraException('Cannot create the folder ' . $destination);

    $initialFolderLength = strlen($source);

    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    /** @var SplFileInfo $splFileInfo */
    foreach ($files as $splFileInfo)
    {
      if ($splFileInfo->isDir())
      {
        $destinationFolder = $destination . $splFileInfo->getFilename();

        if (!file_exists($destinationFolder) && !mkdir($destinationFolder))
          throw new OtraException('Cannot create the folder ' . $destinationFolder);
      } else
      {
        $filePath = $splFileInfo->getRealPath();
        $destinationFilePath = $destination . substr($filePath, $initialFolderLength);

        if (!copy($filePath, $destinationFilePath))
          throw new OtraException(
            'Cannot copy the file \'' . $filePath . ' to ' . $destinationFilePath . '\'.'
          );
      }
    }
  }
}
