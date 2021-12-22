<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createController
{
  use function otra\console\architecture\doWeCreateIt;
  use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, DIR_SEPARATOR};
  use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
  use const otra\console\architecture\constants\{ARG_CONTROLLER_NAME, ARG_BUNDLE_NAME, ARG_MODULE_NAME};

  /** @var bool $consoleForce */
  /** @var bool $interactive */
  require_once CONSOLE_PATH . 'architecture/createController/createController.php';

  // MODULE STEP
  $bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
  $moduleName = $argv[ARG_MODULE_NAME];
  $moduleRelativePath = 'bundles/' . $bundleName . DIR_SEPARATOR . $moduleName;
  $modulePath = BASE_PATH . $moduleRelativePath;
  $controllersFolder = $modulePath . '/controllers/';
  $controllerName = $argv[ARG_CONTROLLER_NAME];
  $controllerPath = $controllersFolder . $controllerName . DIR_SEPARATOR;

  if (!file_exists($controllerPath))
  {
    require CONSOLE_PATH . 'architecture/doWeCreateIt.php';
    doWeCreateIt($interactive, $consoleForce, $controllerName, 'controller');
    createController($controllersFolder, $controllerName, $interactive, $consoleForce);
  }
}
