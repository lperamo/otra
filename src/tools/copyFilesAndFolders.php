<?php
declare(strict_types=1);

use otra\OtraException;

if (!function_exists('copyFileAndFolders'))
{
  /**
   * Copy the file or an entire folder to the destination
   *
   * @param array $filesOrFoldersSrc  Must be the absolute path
   * @param array $filesOrFoldersDest Must be the absolute path
   *
   * @throws OtraException If we can't create a folder or copy a file.
   */
  function copyFileAndFolders(array $filesOrFoldersSrc, array $filesOrFoldersDest): void
  {
    /** @var int $key */
    foreach ($filesOrFoldersSrc as $key => $fileOrFolderSrc)
    {
      $fileOrFolderDest = $filesOrFoldersDest[$key];

      if (is_dir($fileOrFolderSrc) === true)
        iterateOnFilesAndFolders($fileOrFolderSrc, $fileOrFolderDest);
      else
      {
        $destinationFolder = substr($fileOrFolderDest, 0, -strlen(basename($fileOrFolderDest)));

        if (false === file_exists($destinationFolder))
          mkdir($destinationFolder, 0777, true);

        if (false === copy($fileOrFolderSrc, $fileOrFolderDest))
          throw new OtraException('Cannot copy the file \'' . $fileOrFolderSrc . ' to ' . $fileOrFolderDest . '\'.', E_CORE_ERROR);
      }
    }
  }

  /**
   * @param $source
   * @param $destination
   *
   * @throws OtraException
   */
  function iterateOnFilesAndFolders($source, $destination): void
  {
    if (false === file_exists($destination) && false === mkdir($destination, 0777, true))
      throw new OtraException('Cannot create the folder ' . $destination);

    $initialFolderLength = strlen($source);

    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    /** @var SplFileInfo $file */
    foreach ($files as $file)
    {
      if ($file->isDir() === true)
      {
        $destinationFolder = $destination . $file->getFilename();

        if (file_exists($destinationFolder) === false && false === mkdir($destinationFolder))
          throw new OtraException('Cannot create the folder ' . $destinationFolder);
      } else
      {
        $filePath = $file->getRealPath();
        $destinationFilePath = $destination . substr($filePath, $initialFolderLength);

        if (false === copy($filePath, $destinationFilePath))
          throw new OtraException('Cannot copy the file \'' . $file . ' to ' . $destinationFilePath . '\'.');
      }
    }
  }
}
