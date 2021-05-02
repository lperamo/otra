<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use const otra\console\{architecture\createBundle\ARG_INTERACTIVE, CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

$interactive = 'true';

if (isset($argv[ARG_INTERACTIVE]))
{
  $interactive = $argv[ARG_INTERACTIVE];

  if ($interactive !== 'true' && $interactive !== 'false')
  {
    echo CLI_ERROR, 'The parameter ', CLI_INFO_HIGHLIGHT, 'noQuestion ', CLI_ERROR, 'is not correct. You typed ',
      CLI_INFO_HIGHLIGHT, $interactive, CLI_ERROR, '. Type ', CLI_INFO_HIGHLIGHT, 'true', CLI_ERROR, ' or ', CLI_INFO_HIGHLIGHT,
      'false', CLI_ERROR, ' instead.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

$interactive = $interactive === 'true';

