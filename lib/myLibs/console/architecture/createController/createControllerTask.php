<?php
declare(strict_types=1);

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_INTERACTIVE = 5;

$consoleForce = false;

require CORE_PATH . 'console/tools.php';
require CORE_PATH . 'console/architecture/checkInteractiveMode.php';
require CORE_PATH . 'console/architecture/createBundle/checkBundleExistence.php';
require CORE_PATH . 'console/architecture/createModule/checkModuleExistence.php';
require CORE_PATH . 'console/architecture/createController/createController.php';

/** @var string $modulePath */
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];

controllerHandling($interactive, $controllersFolder, $controllerName);
?>
