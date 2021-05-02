<?php
declare(strict_types=1);
namespace otra\console\architecture\createController;
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_CONTROLLER_NAME = 4,
  ARG_INTERACTIVE = 5;

$consoleForce = false;

require CONSOLE_PATH . 'tools.php';
/** @var bool $interactive */
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';
/** @var string $modulePath */
require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
require CONSOLE_PATH . 'architecture/createController/createController.php';

$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];

controllerHandling($interactive, $controllersFolder, $controllerName);

