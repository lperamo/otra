<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModule;

use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_MODULE_NAME};
use function otra\console\architecture\doWeCreateIt;

/**
 * @var bool  $interactive     Do we allow questions to the user?
 * @var bool  $consoleForce    Determines whether we show an error when something is missing in non-interactive mode or
 *                             not. The false value by default will stop the execution if something does not exist
 *                             and shows an error.
 * @var array $argumentsVector
 */
// "_once ..." needed to avoid a repeatable function definition check
require_once CONSOLE_PATH . 'architecture/createModule/createModule.php';

// MODULE STEP
/**
 * @throws OtraException
 */
function checkModuleExistence(string $bundleName, string $moduleName, bool $interactive, bool $consoleForce) : string
{
  $moduleRelativePath = 'bundles/' . $bundleName . DIR_SEPARATOR . $moduleName;
  $modulePath = BASE_PATH . $moduleRelativePath;

  if (!file_exists($modulePath))
  {
    require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
    doWeCreateIt($interactive, $consoleForce, $moduleRelativePath, 'module');

    if (!defined(__NAMESPACE__ . '\\BUNDLE_BASE_PATH'))
      define(__NAMESPACE__ . '\\BUNDLE_BASE_PATH', BUNDLES_PATH . $bundleName . DIR_SEPARATOR);

    createModuleCore(BUNDLE_BASE_PATH, $moduleName, $interactive, $consoleForce);
  }

  return $modulePath;
}
