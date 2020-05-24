<?php
declare(strict_types=1);
$interactive = 'true';

if (array_key_exists(ARG_INTERACTIVE, $argv) === true )
{
  $interactive = $argv[ARG_INTERACTIVE];

  if ($interactive !== 'true' && $interactive !== 'false')
  {
    echo CLI_RED, 'The parameter ', CLI_LIGHT_CYAN, 'noQuestion ', CLI_RED, 'is not correct. You typed ',
      CLI_LIGHT_CYAN, $interactive, CLI_RED, '. Type ', CLI_LIGHT_CYAN, 'true', CLI_RED, ' or ', CLI_LIGHT_CYAN,
      'false', CLI_RED, ' instead.', END_COLOR, PHP_EOL;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
  }
}

$interactive = $interactive === 'true';

