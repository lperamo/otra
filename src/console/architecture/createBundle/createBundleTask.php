<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

require CONSOLE_PATH . 'tools.php';
require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';

const ARG_BUNDLE_NAME = 2,
  ARG_BUNDLE_MASK = 3,
  ARG_INTERACTIVE = 4;

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

bundleHandling($interactive,
  $bundleName,
  $argv[ARG_BUNDLE_MASK] ? intval($argv[ARG_BUNDLE_MASK]) : null,
  true
);

