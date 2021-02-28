<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

if (!function_exists('createFolder'))
{
  /**
   * @param string $absoluteFolderPath
   * @param string $relativeFolderPath Used to recreate the absolute path if the folder already exists.
   * @param string $folderType         Is it a 'controller' folder, 'module' folder ?
   * @param bool   $interactive        Do we have to ask for another folder ?
   *
   * @throws \otra\OtraException
   */
  function createFolder(
    string &$absoluteFolderPath,
    string $relativeFolderPath,
    string $folderType,
    bool $interactive) : void
  {
    while (file_exists($absoluteFolderPath))
    {
      $sentence = CLI_RED . 'The ' . $folderType . ' ' . CLI_LIGHT_CYAN .
        substr($absoluteFolderPath, strlen(BASE_PATH)) . CLI_RED . ' already exists.';

      if (!$interactive)
      {
        echo $sentence, END_COLOR, PHP_EOL;
        throw new \otra\OtraException('', 1, '', NULL, [], true);
      }

      $folderName = promptUser($sentence . ' Try another folder name (type n to stop):');

      if ($folderName === 'n')
        throw new \otra\OtraException('', 0, '', NULL, [], true);

      $absoluteFolderPath = $relativeFolderPath . $folderName;

      // We clean the screen
      echo DOUBLE_ERASE_SEQUENCE;
    }

    mkdir($absoluteFolderPath, 0755);
  }
}

