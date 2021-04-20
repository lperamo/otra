<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

namespace otra\console;

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$bundlePath = BASE_PATH . 'bundles/' . $bundleName . '/';

// BUNDLE STEP
if (!file_exists($bundlePath))
{
  /** @var bool $consoleForce */
  if (!$consoleForce)
    echo CLI_ERROR, 'The bundle ', CLI_INFO_HIGHLIGHT, $bundleName, CLI_ERROR, ' does not exist.', END_COLOR, PHP_EOL;

  /** @var bool $interactive */
  if (!$interactive)
  {
    if (!$consoleForce)
      throw new \otra\OtraException('', 1, '', NULL, [], true);
  } else {
    $answer = promptUser('Do we create it ?(y or n)');

    while ($answer !== 'y' && $answer !== 'n')
    {
      $answer = promptUser('Bad answer. Do we create it ?(y or n)');

      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    if ($answer === 'n')
      throw new \otra\OtraException('', 0, '', NULL, [], true);
  }

  require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';
  /** @var int $bundleMask */
  bundleHandling($interactive, $bundleName, $consoleForce ? $bundleMask : null);
}

