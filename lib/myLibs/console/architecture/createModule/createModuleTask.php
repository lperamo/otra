<?php
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/module/CreateModule.php';

const ARG_BUNDLE_NAME = 2,
  ARG_MODULE_NAME = 3,
  ARG_INTERACTIVE = 4;

require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/bundle/CheckBundleExistence.php';

$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
moduleHandling($interactive, $bundleName , $argv[ARG_MODULE_NAME]);
?>
