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

require CONSOLE_PATH . 'architecture/createModule/createModule.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . DIR_SEPARATOR . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;

if (!file_exists($modulePath))
{
  /** @var bool $consoleForce */
  if (!$consoleForce)
    echo CLI_ERROR, 'The module ', CLI_INFO_HIGHLIGHT, $moduleRelativePath, CLI_ERROR, ' does not exist.' , END_COLOR,
      PHP_EOL;

  require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
  /** @var bool $interactive */
  doWeCreateIt($interactive, $consoleForce);

  if (!defined('otra\console\architecture\createModule\BUNDLE_BASE_PATH'))
    define('otra\console\architecture\createModule\BUNDLE_BASE_PATH', BUNDLES_PATH . $bundleName . DIR_SEPARATOR);

  createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
}

