<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use const otra\console\{CLI_ERROR,CLI_INFO_HIGHLIGHT,END_COLOR};

/**
 * @param array  $arguments        Must pass $argumentsVector
 * @param int    $argumentPosition The index of the $argumentsVector array to check
 * @param string $argumentName     Like 'interactive' or 'force'
 * @param string $defaultValue
 *
 * @throws OtraException
 * @return bool
 */
function checkBooleanArgument(
  array $arguments,
  int $argumentPosition,
  string $argumentName,
  string $defaultValue = 'true'
): bool
{
  $testedArgument = $defaultValue;

  if (isset($arguments[$argumentPosition]))
  {
    $testedArgument = $arguments[$argumentPosition];

    if ($testedArgument !== 'true' && $testedArgument !== 'false')
    {
      echo CLI_ERROR, 'The parameter ', CLI_INFO_HIGHLIGHT, $argumentName, ' ', CLI_ERROR, 'is not correct. You typed ',
      CLI_INFO_HIGHLIGHT, $testedArgument, CLI_ERROR, '. Type ', CLI_INFO_HIGHLIGHT, 'true', CLI_ERROR, ' or ',
      CLI_INFO_HIGHLIGHT, 'false', CLI_ERROR, ' instead.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }
  }

  return $testedArgument === 'true';
}
