<?php
if (function_exists('createFolder') === false)
{
  /**
   * @param string $absoluteFolderPath
   * @param string $relativeFolderPath Used to recreate the absolute path if the folder already exists.
   * @param string $folderName         Used if the folder does not exists.
   * @param string $folderType         Is it a 'controller' folder, 'module' folder ?
   * @param bool   $interactive        Do we have to ask for another folder ?
   */
  function createFolder(string &$absoluteFolderPath, string $relativeFolderPath, string $folderName, string $folderType,
                        bool $interactive)
  {
    while (file_exists($absoluteFolderPath) === true)
    {
      $sentence = CLI_RED . 'The ' . $folderType . ' ' . CLI_LIGHT_CYAN . $folderName . CLI_RED . ' already exists.';

      if ($interactive === false)
      {
        echo $sentence, PHP_EOL;
        exit(1);
      }

      $folderName = promptUser($sentence . ' Try another folder name (type n to stop):');

      if ($folderName === 'n')
        exit(0);

      $absoluteFolderPath = $relativeFolderPath . $folderName;

      // We clean the screen
      echo DOUBLE_ERASE_SEQUENCE;
    }

    mkdir($absoluteFolderPath, 0755);
  }
}
?>