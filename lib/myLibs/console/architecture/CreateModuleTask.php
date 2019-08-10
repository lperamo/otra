<?php
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CreateModule.php';

const BUNDLE_NAME = 2,
  MODULE_NAME = 3,
  INTERACTIVE = 4;

require CORE_PATH . 'console/architecture/CheckInteractiveMode.php';
require CORE_PATH . 'console/architecture/CheckBundleExistence.php';

$bundleName = ucfirst($argv[BUNDLE_NAME]);
moduleHandling($interactive, $bundleName , $argv[MODULE_NAME]);
?>