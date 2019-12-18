<?php
declare(strict_types=1);

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_ACTION_NAME = 5,
  ARG_INTERACTIVE = 6;

$consoleForce = false;

require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/bundle/CheckBundleExistence.php';
require CORE_PATH . 'console/architecture/module/CheckModuleExistence.php';
require CORE_PATH . 'console/architecture/controller/CheckControllerExistence.php';
require CORE_PATH . 'console/architecture/action/CreateAction.php';

actionHandling(
  $interactive,
  $bundleName,
  $moduleName,
  $controllerName,
  $controllerPath,
  $argv[ARG_ACTION_NAME]
);
?>
