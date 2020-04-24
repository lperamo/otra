<?php

namespace otra\console;

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$bundlePath = BASE_PATH . 'bundles/' . $bundleName . '/';

// BUNDLE STEP
if (file_exists($bundlePath) === false)
{
  /** @var bool $consoleForce */
  if ($consoleForce === false)
    echo CLI_RED, 'The bundle ', CLI_LIGHT_CYAN, $bundleName, CLI_RED, ' does not exist.', END_COLOR, PHP_EOL;

  /** @var bool $interactive */
  if ($interactive === false)
  {
    if ($consoleForce === false)
      exit(1);
  } else {
    $answer = promptUser('Do we create it ?(y or n)');

    while ($answer !== 'y' && $answer !== 'n')
    {
      $answer = promptUser('Bad answer. Do we create it ?(y or n)');

      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    if ($answer === 'n')
      exit (0);
  }

  require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';
  /** @var int $bundleMask */
  bundleHandling($bundleName, $consoleForce ? $bundleMask : null);
}
?>
