<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);


namespace otra\console\architecture\constants
{
  const
    ARG_BUNDLE_NAME = 2,
    ARG_CONTROLLER_NAME = 4,
    ARG_INTERACTIVE = 5,
    ARG_MODULE_NAME = 3;
}

namespace otra\console\architecture\createController
{
  use const otra\config\CONSOLE_PATH;
  use const otra\console\architecture\constants\ARG_CONTROLLER_NAME;

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
}
