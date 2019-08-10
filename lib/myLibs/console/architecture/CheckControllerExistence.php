<?php

namespace lib\myLibs\console;

require CORE_PATH . 'console/architecture/CreateController.php';

// MODULE STEP
$bundleName = ucfirst($argv[BUNDLE_NAME]);
$moduleName = $argv[MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[CONTROLLER_NAME];
$controllerPath = $controllersFolder . $controllerName . '/';

if (file_exists($controllerPath) === false)
{
  echo CLI_RED, 'The controller ', CLI_LIGHT_CYAN, $controllerName, CLI_RED, ' does not exist.' , END_COLOR,
  PHP_EOL;

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

  createController($controllersFolder, $controllerName, $interactive);
}
?>