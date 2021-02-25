<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_ACTION_NAME = 5,
  ARG_INTERACTIVE = 6;

$consoleForce = false;

require CONSOLE_PATH . 'tools.php';
/** @var bool $interactive */
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
/** @var string $bundleName */
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';
/** @var string $moduleName */
require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
/** @var string $controllerName */
/** @var string $controllerPath */
require CONSOLE_PATH . 'architecture/createController/checkControllerExistence.php';
require CONSOLE_PATH . 'architecture/createAction/createAction.php';

actionHandling(
  $interactive,
  $bundleName,
  $moduleName,
  $controllerName,
  $controllerPath,
  $argv[ARG_ACTION_NAME]
);

