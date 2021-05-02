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
    ARG_INTERACTIVE = 4,
    ARG_MODULE_NAME = 3;
}

namespace otra\console\architecture\createModule
{
  use const otra\config\CONSOLE_PATH;
  use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_MODULE_NAME};

  // loading functions, not executing anything
  require CONSOLE_PATH . 'tools.php';
  require CONSOLE_PATH . 'architecture/createModule/createModule.php';

  $consoleForce = false;
  /** @var bool $interactive */
  require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
  require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';

  $bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
  moduleHandling($interactive, $bundleName , $argv[ARG_MODULE_NAME]);
}

