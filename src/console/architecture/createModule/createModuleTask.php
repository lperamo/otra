<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\constants
{
  const
    ARG_BUNDLE_NAME = 2,
    ARG_FORCE = 5,
    ARG_INTERACTIVE = 4,
    ARG_MODULE_NAME = 3;
}

namespace otra\console\architecture\createModule
{
  use otra\OtraException;
  use const otra\cache\php\CONSOLE_PATH;
  use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_FORCE, ARG_INTERACTIVE, ARG_MODULE_NAME};
  use function otra\console\architecture\checkBooleanArgument;

  /**
   * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
   *
   * @throws OtraException
   * @return void
   */
  function createModule(array $argumentsVector) : void
  {
    // loading functions, not executing anything
    require CONSOLE_PATH . 'tools.php';
    require_once CONSOLE_PATH . 'architecture/createModule/createModule.php';
    require CONSOLE_PATH . 'architecture/checkBooleanArgument.php';

    $interactive = checkBooleanArgument($argumentsVector, ARG_INTERACTIVE, 'interactive');
    $consoleForce = checkBooleanArgument($argumentsVector, ARG_FORCE, 'force', 'false');
    require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';

    $bundleName = ucfirst($argumentsVector[ARG_BUNDLE_NAME]);
    moduleHandling($interactive, $consoleForce, $bundleName , $argumentsVector[ARG_MODULE_NAME]);
  }
}
