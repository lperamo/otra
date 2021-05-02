<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);
namespace otra\console\architecture\createModule;
use function otra\console\architecture\doWeCreateIt;
use const otra\console\{CLI_ERROR,CLI_INFO_HIGHLIGHT,END_COLOR};

require CONSOLE_PATH . 'architecture/createModule/createModule.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
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

  if (!defined('BUNDLE_BASE_PATH'))
    define('BUNDLE_BASE_PATH', BASE_PATH . 'bundles/' . $bundleName . '/');

  createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
}

