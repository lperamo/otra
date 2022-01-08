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

/**
 * @var bool  $consoleForce
 * @var bool  $interactive
 * @var array $argumentsVector
 */

$bundleName = ucfirst($argumentsVector[ARG_BUNDLE_NAME]);
$bundlePath = BUNDLES_PATH . $bundleName . DIR_SEPARATOR;

// BUNDLE STEP
if (!file_exists($bundlePath))
{
  require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
  doWeCreateIt($interactive, $consoleForce, $bundleName, 'bundle');
  require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';
  /** @var int $bundleMask */
  bundleHandling($interactive, $consoleForce, $bundleName, $bundleMask ?? null);
}
