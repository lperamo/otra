<?php
declare(strict_types=1);
namespace lib\myLibs\console;

use SebastianBergmann\CodeCoverage\Report\PHP;

require CORE_PATH . 'console/Tools.php';
const BUNDLE_NAME = 2,
  MODULE_NAME = 3,
  CONTROLLER_NAME = 4;

$bundlePath = BASE_PATH . 'bundles/' . $argv[BUNDLE_NAME] . '/';

// BUNDLE STEP
if (file_exists($bundlePath) === false)
{
  echo CLI_RED, 'The bundle ', CLI_LIGHT_CYAN, $argv[BUNDLE_NAME], CLI_RED, ' does not exist' , END_COLOR, PHP_EOL;
  $answer = promptUser('Do we create it ?(y or n)');

  while ($answer !== 'y' && $answer !== 'n')
  {
    $answer = promptUser('Bad answer. Do we create it ?(y or n)');
    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  if ($answer === 'n')
    exit (0);

  Tasks::createBundle(['console.php', 'createBundle', 'testi']);
}

// MODULE STEP
$moduleRelativePath = 'bundles/' . $argv[BUNDLE_NAME] . '/' . $argv[MODULE_NAME];
$modulePath = BASE_PATH . $moduleRelativePath;

if (file_exists($modulePath) === false)
{
  echo CLI_RED, 'The module ', CLI_LIGHT_CYAN, $moduleRelativePath, CLI_RED, ' does not exist' , END_COLOR, PHP_EOL;
  $answer = promptUser('Do we create it ?(y or n)');

  while ($answer !== 'y' && $answer !== 'n')
  {
    if ($answer === 'n')
      exit(0);

    $answer = promptUser('Bad answer. Do we create it ?(y or n)');
    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  mkdir($modulePath, 0755);
  echo CLI_LIGHT_GREEN, 'Module ', CLI_LIGHT_CYAN, $moduleRelativePath, CLI_LIGHT_GREEN, ' created.', PHP_EOL;
}

// CONTROLLER STEP
$controllersFolder = $modulePath . '/controllers/';
mkdir($controllersFolder, 0755);
echo CLI_LIGHT_GREEN, 'Folder ', CLI_LIGHT_CYAN, substr($controllersFolder, 0, -1), CLI_LIGHT_GREEN,
  ' created.', PHP_EOL;

$controllerName = $argv[CONTROLLER_NAME];

while($controllerName !== 'n')
{
  mkdir($controllersFolder . $controllerName, 0755);
  echo CLI_GREEN, 'Controller ' , CLI_LIGHT_CYAN, $controllerName, CLI_GREEN, ' created.', PHP_EOL;

  $controllerName = promptUser('What is the name of the next controller ? (type n to stop)');
}
?>