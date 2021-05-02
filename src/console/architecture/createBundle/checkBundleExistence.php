<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);
namespace otra\console\architecture\createBundle;

use function otra\console\architecture\doWeCreateIt;
use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\architecture\constants\ARG_BUNDLE_NAME;

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$bundlePath = BUNDLES_PATH . $bundleName . DIR_SEPARATOR;

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

