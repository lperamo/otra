<?php
declare(strict_types=1);

const BUNDLE_NAME = 2,
  MODULE_NAME = 3,
  CONTROLLER_NAME = 4,
  INTERACTIVE = 5;

require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/CheckBundleExistence.php';
require CORE_PATH . 'console/architecture/CheckModuleExistence.php';
require CORE_PATH . 'console/architecture/CreateController.php';

/** @var string $modulePath */
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[CONTROLLER_NAME];

controllerHandling($interactive, $controllersFolder, $controllerName);
?>