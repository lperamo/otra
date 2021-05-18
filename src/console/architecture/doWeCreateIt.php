<?php
declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use function otra\console\promptUser;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, ERASE_SEQUENCE};

if (!function_exists('otra\console\architecture\doWeCreateIt'))
{
  /**
   * Checks if we have to show a warning or an error when the folder is missing.
   * Then it asks to the user if we have to create it.
   *
   * @author Lionel Péramo
   *
   * @param bool $interactive  False, no question will be asked but the status messages are shown.
   * @param bool $consoleForce Determines whether we show an error when something is missing in non interactive mode or
   *                           not. The false value by default will stop the execution if something does not exist and
   *                           show an error.
   * @param string $folderPath
   * @param string $folderType 'bundle', 'module' etc.
   *
   * @throws OtraException
   * @package otra\console\architecture
   */
  function doWeCreateIt(bool $interactive, bool $consoleForce, string $folderPath, string $folderType = 'folder') : void
  {
    if ($interactive)
      echo CLI_WARNING, 'The ' . $folderType . ' ', CLI_INFO_HIGHLIGHT, $folderPath, CLI_WARNING, ' does not exist.',
        END_COLOR, PHP_EOL;
    elseif (!$consoleForce)
    {
      echo CLI_ERROR, 'The ' . $folderType . ' ', CLI_INFO_HIGHLIGHT, $folderPath, CLI_ERROR, ' does not exist.',
        END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', null, [], true);
    } else
      return;

    $answer = promptUser('Do we create it ?(y or n)');

    while ($answer !== 'y' && $answer !== 'n')
    {
      $answer = promptUser('Bad answer. Do we create it ?(y or n)');

      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    if ($answer === 'n')
      throw new OtraException('', 0, '', null, [], true);
  }
}
