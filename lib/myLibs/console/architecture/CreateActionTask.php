<?php
declare(strict_types=1);

const BUNDLE_NAME = 2,
  MODULE_NAME = 3,
  CONTROLLER_NAME = 4,
  ACTION_NAME = 5,
  INTERACTIVE = 6;

require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/CheckBundleExistence.php';
require CORE_PATH . 'console/architecture/CheckModuleExistence.php';
require CORE_PATH . 'console/architecture/CheckControllerExistence.php';
require CORE_PATH . 'console/architecture/CreateAction.php';

actionHandling($interactive, $bundleName, $moduleName, $controllerName, $controllerPath, $argv[ACTION_NAME]);
?>