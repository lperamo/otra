<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createController;

use function otra\console\architecture\doWeCreateIt;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

require CONSOLE_PATH . 'architecture/createController/createController.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];
$controllerPath = $controllersFolder . $controllerName . '/';

if (!file_exists($controllerPath))
{
  /** @var bool $consoleForce */
  if (!$consoleForce)
    echo CLI_ERROR, 'The controller ', CLI_INFO_HIGHLIGHT, $moduleRelativePath . '/controllers/' . $controllerName, CLI_ERROR,
    ' does not exist.' , END_COLOR, PHP_EOL;

  require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
  /** @var bool $interactive */
  doWeCreateIt($interactive, $consoleForce);
  createController($controllersFolder, $controllerName, $interactive);
}

