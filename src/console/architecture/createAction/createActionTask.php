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
    ARG_FORCE = 7,
    ARG_INTERACTIVE = 6,
    ARG_MODULE_NAME = 3;
}

namespace otra\console\architecture\createAction
{

  use otra\OtraException;
  use const otra\cache\php\CONSOLE_PATH;
  use const otra\console\architecture\constants\
  {ARG_CONTROLLER_NAME, ARG_FORCE, ARG_INTERACTIVE, ARG_MODULE_NAME};
  use function otra\console\architecture\
  {actionHandling, checkBooleanArgument, createController\checkControllerExistence};
  use function otra\console\architecture\createModule\checkModuleExistence;

  const ARG_ACTION_NAME = 5;

  /**
   * @throws OtraException
   */
  function createAction(array $argumentsVector): void
  {
    $consoleForce = false;

    require_once CONSOLE_PATH . 'tools.php';
    require_once CONSOLE_PATH . 'architecture/checkBooleanArgument.php';
    $interactive = checkBooleanArgument($argumentsVector, ARG_INTERACTIVE, 'interactive');
    $consoleForce = checkBooleanArgument($argumentsVector, ARG_FORCE, 'force', 'false');

    /** @var string $bundleName */
    $bundleName = require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';

    /** @var string $moduleName */
    require_once CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
    $moduleName = $argumentsVector[ARG_MODULE_NAME];
    checkModuleExistence($bundleName, $moduleName, $interactive, $consoleForce);
    /** @var string $controllerPath */
    require_once CONSOLE_PATH . 'architecture/createController/checkControllerExistence.php';
    $controllerName = $argumentsVector[ARG_CONTROLLER_NAME];
    $controllerPath = checkControllerExistence($bundleName, $moduleName, $controllerName, $interactive, $consoleForce);
    require_once CONSOLE_PATH . 'architecture/createAction/createAction.php';
    actionHandling(
      $interactive,
      $bundleName,
      $moduleName,
      $controllerName,
      $controllerPath,
      $argumentsVector[ARG_ACTION_NAME]
    );
  }

}
