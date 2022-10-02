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
 * @var bool  $consoleForce    Determines whether we show an error when something is missing in non-interactive mode
 *                               or not. The false value by default will stop the execution if something does not exist
 *                               and shows an error.
 * @var bool  $interactive     Do we allow questions to the user?
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
