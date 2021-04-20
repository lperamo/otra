<?php
declare(strict_types=1);

use otra\OtraException;

if (!function_exists('doWeCreateIt'))
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
  function doWeCreateIt(bool $interactive, bool $consoleForce)
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
