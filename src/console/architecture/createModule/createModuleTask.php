<?php
declare(strict_types=1);
namespace otra\console\architecture\createModule;
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

// loading functions, not executing anything
require CONSOLE_PATH . 'tools.php';
require CONSOLE_PATH . 'architecture/createModule/createModule.php';

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_INTERACTIVE = 4;

$consoleForce = false;
/** @var bool $interactive */
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';
require CONSOLE_PATH . 'architecture/createBundle/checkBundleExistence.php';

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
moduleHandling($interactive, $bundleName , $argv[ARG_MODULE_NAME]);

