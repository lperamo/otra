<?php
declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use function otra\console\promptUser;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, ERASE_SEQUENCE};

if (!function_exists('otra\console\architecture\doWeCreateIt'))
{
  /**
   * @author Lionel Péramo
   * @package otra\console\architecture
   *
   * @param bool $interactive  False, no question will be asked but the status messages are shown.
   * @param bool $consoleForce
   *
   * @throws OtraException
   */
  function doWeCreateIt(bool $interactive, bool $consoleForce) : void
  {
    if (!$interactive)
    {
      if (!$consoleForce)
        throw new OtraException('', 1, '', null, [], true);
    } else
    {
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
}
