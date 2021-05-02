<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\constants
{
  const
    ARG_INTERACTIVE = 4,
    ARG_BUNDLE_NAME = 2;
}

namespace otra\console\architecture\createBundle
{
  use const otra\config\CONSOLE_PATH;
  use const otra\console\ERASE_SEQUENCE;
  use const otra\console\architecture\constants\ARG_BUNDLE_NAME;
  use function otra\console\promptUser;

  require CONSOLE_PATH . 'tools.php';
  require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';

  const ARG_BUNDLE_MASK = 3;

  $consoleForce = false;
  /** @var bool $interactive */
  require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';

// Checking argument : bundle name
  if (!isset($argv[ARG_BUNDLE_NAME]))
  {
    $bundleName = promptUser('You did not specified a name for the bundle. What is it ?');

    // We clean the screen
    echo ERASE_SEQUENCE;
  } else
    $bundleName = $argv[ARG_BUNDLE_NAME];

  bundleHandling(
    $interactive,
    $bundleName,
    $argv[ARG_BUNDLE_MASK] ? intval($argv[ARG_BUNDLE_MASK]) : null,
    true
  );
}

