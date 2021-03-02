<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

namespace otra\console;

require CONSOLE_PATH . 'architecture/createController/createController.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;
$controllersFolder = $modulePath . '/controllers/';
$controllerName = $argv[ARG_CONTROLLER_NAME];
$controllerPath = $controllersFolder . $controllerName . '/';

if (!file_exists($controllerPath))
{
  /** @var bool $consoleForce */
  if (!$consoleForce)
    echo CLI_RED, 'The controller ', CLI_LIGHT_CYAN, $moduleRelativePath . '/controllers/' . $controllerName, CLI_RED,
    ' does not exist.' , END_COLOR, PHP_EOL;

  /** @var bool $interactive */
  if (!$interactive)
  {
    if (!$consoleForce)
      throw new \otra\OtraException('', 1, '', NULL, [], true);
  } else {
    $answer = promptUser('Do we create it ?(y or n)');

    while ($answer !== 'y' && $answer !== 'n')
    {
      $answer = promptUser('Bad answer. Do we create it ?(y or n)');
      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    if ($answer === 'n')
      throw new \otra\OtraException('', 0, '', NULL, [], true);
  }

  createController($controllersFolder, $controllerName, $interactive);
}

