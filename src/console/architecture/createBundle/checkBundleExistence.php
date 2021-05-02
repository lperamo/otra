<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);
namespace otra\console\architecture\createBundle;

use function otra\console\architecture\doWeCreateIt;
use const otra\console\{CLI_ERROR,CLI_INFO_HIGHLIGHT,END_COLOR};

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$bundlePath = BASE_PATH . 'bundles/' . $bundleName . '/';

// BUNDLE STEP
if (!file_exists($bundlePath))
{
  /** @var bool $consoleForce */
  if (!$consoleForce)
    echo CLI_ERROR, 'The bundle ', CLI_INFO_HIGHLIGHT, $bundleName, CLI_ERROR, ' does not exist.', END_COLOR, PHP_EOL;

  require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
  /** @var bool $interactive */
  doWeCreateIt($interactive, $consoleForce);

  require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';
  /** @var int $bundleMask */
  bundleHandling($interactive, $bundleName, $consoleForce ? $bundleMask : null);
}

