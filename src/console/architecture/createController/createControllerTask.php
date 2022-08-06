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
    ARG_FORCE = 6,
    ARG_INTERACTIVE = 5,
    ARG_MODULE_NAME = 3;
}

namespace otra\console\architecture\createController
{
  use otra\OtraException;
  use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH};
  use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
  use const otra\console\architecture\constants\{ARG_CONTROLLER_NAME, ARG_FORCE, ARG_INTERACTIVE};
  use function otra\console\architecture\checkBooleanArgument;

  /**
   * @throws OtraException
   * @return void
   */
  function createController(array $argumentsVector) : void
  {
    if (!file_exists(BUNDLES_PATH))
    {
      echo CLI_ERROR, 'There is no ', CLI_INFO_HIGHLIGHT, 'bundles', CLI_ERROR,
      ' folder to put bundles! Please create this folder or launch ', CLI_INFO_HIGHLIGHT, 'otra init', CLI_ERROR,
      ' to solve it.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    require CONSOLE_PATH . 'tools.php';
    require CONSOLE_PATH . 'architecture/checkBooleanArgument.php';
    $interactive = checkBooleanArgument($argumentsVector, ARG_INTERACTIVE, 'interactive');
    $consoleForce = checkBooleanArgument($argumentsVector, ARG_FORCE, 'force', 'false');
    require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';

    /** @var string $modulePath */
    require CONSOLE_PATH . 'architecture/createModule/checkModuleExistence.php';
    require CONSOLE_PATH . 'architecture/createController/createController.php';

    $controllersFolder = $modulePath . '/controllers/';
    $controllerName = $argumentsVector[ARG_CONTROLLER_NAME];

    controllerHandling($interactive, $consoleForce, $controllersFolder, $controllerName);
  }
}
