<?php

namespace lib\myLibs\console;

require CORE_PATH . 'console/architecture/CreateModule.php';

// MODULE STEP
$bundleName = $argv[BUNDLE_NAME];
$moduleName = $argv[MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;

if (file_exists($modulePath) === false)
{
  echo CLI_RED, 'The module ', CLI_LIGHT_CYAN, $moduleRelativePath, CLI_RED, ' does not exist.' , END_COLOR, PHP_EOL;

  /** @var $interactive */
  if ($interactive === false)
    exit(1);

  $answer = promptUser('Do we create it ?(y or n)');

  while ($answer !== 'y' && $answer !== 'n')
  {
    $answer = promptUser('Bad answer. Do we create it ?(y or n)');
    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  if ($answer === 'n')
    exit(0);

  if (defined('BUNDLE_BASE_PATH') === false)
    define('BUNDLE_BASE_PATH', BASE_PATH . 'bundles/' . $bundleName . '/');

  createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
}
?>