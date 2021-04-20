<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

$interactive = 'true';

if (isset($argv[ARG_INTERACTIVE]))
{
  $interactive = $argv[ARG_INTERACTIVE];

  if ($interactive !== 'true' && $interactive !== 'false')
  {
    echo CLI_ERROR, 'The parameter ', CLI_INFO_HIGHLIGHT, 'noQuestion ', CLI_ERROR, 'is not correct. You typed ',
      CLI_INFO_HIGHLIGHT, $interactive, CLI_ERROR, '. Type ', CLI_INFO_HIGHLIGHT, 'true', CLI_ERROR, ' or ', CLI_INFO_HIGHLIGHT,
      'false', CLI_ERROR, ' instead.', END_COLOR, PHP_EOL;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
  }
}

$interactive = $interactive === 'true';

