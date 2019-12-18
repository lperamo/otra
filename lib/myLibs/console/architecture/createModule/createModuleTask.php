<?php
declare(strict_types=1);
require CORE_PATH . 'console/tools.php';
require CORE_PATH . 'console/architecture/createModule/createModule.php';

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_INTERACTIVE = 4;

require CORE_PATH . 'console/architecture/checkInteractiveMode.php';
require CORE_PATH . 'console/architecture/createBundle/checkBundleExistence.php';

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
moduleHandling($interactive, $bundleName , $argv[ARG_MODULE_NAME]);
?>
