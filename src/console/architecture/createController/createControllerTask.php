<?php
declare(strict_types=1);

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_INTERACTIVE = 5;

$consoleForce = false;

require CONSOLE_PATH . 'tools.php';
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';
require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
require CONSOLE_PATH . 'architecture/createController/createController.php';

/** @var string $modulePath */
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];

controllerHandling($interactive, $controllersFolder, $controllerName);

