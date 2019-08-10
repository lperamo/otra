<?php
$interactive = 'true';

if (array_key_exists(INTERACTIVE, $argv) === true )
{
  $interactive = $argv[INTERACTIVE];

  if ($interactive !== 'true' && $interactive !== 'false')
  {
    echo CLI_RED, 'The parameter ', CLI_LIGHT_CYAN, 'noQuestion ', CLI_RED, 'is not correct. Type ', CLI_LIGHT_CYAN, 'true',
    CLI_RED, ' or ', CLI_LIGHT_CYAN, 'false', CLI_RED, ' instead.', END_COLOR, PHP_EOL;
    exit(1);
  }
}

$interactive = $interactive === 'true';
?>