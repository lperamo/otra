<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModule;

use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_MODULE_NAME};
use function otra\console\architecture\doWeCreateIt;

/** @var bool $interactive */
/** @var bool $consoleForce */
// "_once ..." needed to avoid a repeatable function definition check
require_once CONSOLE_PATH . 'architecture/createModule/createModule.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
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
