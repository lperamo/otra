<?php
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CreateModule.php';

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_INTERACTIVE = 4;

require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/CheckBundleExistence.php';

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
moduleHandling($interactive, $bundleName , $argv[ARG_MODULE_NAME]);
?>