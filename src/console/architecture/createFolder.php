<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */

declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use const otra\cache\php\BASE_PATH;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\promptUser;

if (!function_exists(__NAMESPACE__ . '\\createFolder'))
{
  /**
   * @param string $relativeFolderPath Used to recreate the absolute path if the folder already exists.
   * @param string $folderType         Is it a 'controller' folder, 'module' folder ?
   * @param bool   $interactive        Do we allow questions to the user?
   * @param bool $consoleForce         Determines whether we show an error when something is missing in non-interactive
   *                                   mode or not. The false value by default will stop the execution if something does
   *                                   not exist and show an error.
   * @throws OtraException
   */
  function createFolder(
    string &$absoluteFolderPath,
    string $relativeFolderPath,
    string $folderType,
    bool $interactive,
    bool $consoleForce
  ) : void
  {
    while ($pathExists = file_exists($absoluteFolderPath))
    {
      $sentence = CLI_ERROR . 'The ' . $folderType . ' ' . CLI_INFO_HIGHLIGHT .
        substr($absoluteFolderPath, strlen(BASE_PATH)) . CLI_ERROR . ' already exists.';

      if ($consoleForce)
        break;

      if (!$interactive)
      {
        echo $sentence, END_COLOR, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }

      $folderName = promptUser($sentence . ' Try another folder name (type n to stop):');

      if ($folderName === 'n')
        throw new OtraException(exit: true);

      $absoluteFolderPath = $relativeFolderPath . $folderName;

      // We clean the screen
      echo DOUBLE_ERASE_SEQUENCE;
    }

    if (!$pathExists)
      mkdir($absoluteFolderPath, 0755);
  }
}
