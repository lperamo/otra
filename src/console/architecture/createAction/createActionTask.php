<?php
declare(strict_types=1);

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_ACTION_NAME = 5,
  ARG_INTERACTIVE = 6;

$consoleForce = false;

require CONSOLE_PATH . 'tools.php';
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';
require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
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
?>
