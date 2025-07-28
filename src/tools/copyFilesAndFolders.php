<?php
/**
 * @author Lionel Péramo
 * @package otra\tools
 */
declare(strict_types=1);
namespace otra\tools;

use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tools\files\returnLegiblePath;

if (!function_exists(__NAMESPACE__ . '\\copyFileAndFolders'))
{
  if (!defined('SUCCESS'))
    require_once CONSOLE_PATH . 'colors.php';

  /**
   * @throws OtraException
   */
  function cannotCopy(string $source, string $destination): never
  {
    $error = error_get_last();
    require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
    throw new OtraException(
      'Cannot copy the file ' . returnLegiblePath($source) . ' to ' .
      returnLegiblePath($destination) . '.' . PHP_EOL . 'Error type ' . CLI_INFO_HIGHLIGHT .
      $error['type'] . CLI_BASE . ' : ' . $error['message'] . ' at ' . CLI_INFO_HIGHLIGHT . $error['file'] .
      CLI_BASE . ':' . CLI_INFO_HIGHLIGHT . $error['line'] . END_COLOR . PHP_EOL,
      $error['type']
    );
  }

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
    // Prevent the umask from changing the permissions we are setting
    $oldUmask = umask(0);

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
          mkdir($destinationFolder, 0775, true);

        if (!copy($fileOrFolderSrc, $fileOrFolderDest))
          cannotCopy($fileOrFolderSrc, $fileOrFolderDest);
      }
    }

    umask($oldUmask);
  }

  /**
   *
   * @throws OtraException
   */
  function iterateOnFilesAndFolders(string $source, string $destination): void
  {
    if (!file_exists($destination) && !mkdir($destination, 0775, true))
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
        $destinationFolder = $destination . mb_substr($splFileInfo->getPath(), $initialFolderLength) . '/' .
          $splFileInfo->getFilename();

        if (!file_exists($destinationFolder) && !mkdir($destinationFolder, 0775, true))
          throw new OtraException('Cannot create the folder ' . $destinationFolder);
      } else
      {
        $filePath = $splFileInfo->getRealPath();
        $destinationFilePath = $destination . mb_substr($filePath, $initialFolderLength);
        $destinationFolder = mb_substr($destinationFilePath, 0, mb_strrpos($destinationFilePath, '/'));

        if (!file_exists($destinationFolder) && !mkdir($destinationFolder, 0775, true))
          throw new OtraException('Cannot create the folder ' . $destinationFolder);

        if (!copy($filePath, $destinationFilePath))
          cannotCopy($filePath, $destinationFilePath);
      }
    }
  }
}
