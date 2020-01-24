<?php

namespace src\console;

require CORE_PATH . 'console/architecture/createController/createController.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];
$controllerPath = $controllersFolder . $controllerName . '/';

if (file_exists($controllerPath) === false)
{
  /** @var bool $consoleForce */
  if ($consoleForce === false)
    echo CLI_RED, 'The controller ', CLI_LIGHT_CYAN, $controllerName, CLI_RED, ' does not exist.' , END_COLOR,
      PHP_EOL;

  /** @var bool $interactive */
  if ($interactive === false)
  {
    if ($consoleForce === false)
      exit(1);
  } else {
    $answer = promptUser('Do we create it ?(y or n)');

    while ($answer !== 'y' && $answer !== 'n')
    {
      $answer = promptUser('Bad answer. Do we create it ?(y or n)');
      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    if ($answer === 'n')
      exit(0);
  }

  createController($controllersFolder, $controllerName, $interactive);
}
?>
