<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

namespace otra\console;

require CONSOLE_PATH . 'architecture/createModule/createModule.php';

// MODULE STEP
$bundleName = ucfirst($argv[ARG_BUNDLE_NAME]);
$moduleName = $argv[ARG_MODULE_NAME];
$moduleRelativePath = 'bundles/' . $bundleName . '/' . $moduleName;
$modulePath = BASE_PATH . $moduleRelativePath;

if (file_exists($modulePath) === false)
{
  /** @var bool $consoleForce */
  if ($consoleForce === false)
    echo CLI_RED, 'The module ', CLI_LIGHT_CYAN, $moduleRelativePath, CLI_RED, ' does not exist.' , END_COLOR, PHP_EOL;

  /** @var bool $interactive */
  if ($interactive === false)
  {
    if ($consoleForce === false)
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
      exit(0);
  }

  if (defined('BUNDLE_BASE_PATH') === false)
    define('BUNDLE_BASE_PATH', BASE_PATH . 'bundles/' . $bundleName . '/');

  createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
}

