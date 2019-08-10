<?php

namespace lib\myLibs\console;

$bundlePath = BASE_PATH . 'bundles/' . $argv[BUNDLE_NAME] . '/';

// BUNDLE STEP
if (file_exists($bundlePath) === false)
{
  $bundleName = $argv[BUNDLE_NAME];
  echo CLI_RED, 'The bundle ', CLI_LIGHT_CYAN, $bundleName, CLI_RED, ' does not exist.', END_COLOR, PHP_EOL;

  /** @var $interactive */
  if ($interactive === 'false')
    exit(1);

  $answer = promptUser('Do we create it ?(y or n)');

  while ($answer !== 'y' && $answer !== 'n')
  {
    $answer = promptUser('Bad answer. Do we create it ?(y or n)');

    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  if ($answer === 'n')
    exit (0);

  Tasks::createBundle(['console.php', 'createBundle', $bundleName]);
}
?>