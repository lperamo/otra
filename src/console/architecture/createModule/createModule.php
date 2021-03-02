<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

require CONSOLE_PATH . 'architecture/createFolder.php';

if (function_exists('createModule') === false)
{
  /**
   * @param string $bundleBasePath The path where we put modules
   * @param string $moduleName
   * @param bool   $interactive
   *
   * @throws \otra\OtraException
   */
  function createModule(string $bundleBasePath, string $moduleName, bool $interactive): void
  {
    $modulePath = $bundleBasePath . $moduleName;

    // If the folder does not exist and we are not in interactive mode, we exit the program.
    createFolder($modulePath, $bundleBasePath, 'module', $interactive);

    mkdir($modulePath . '/controllers', 0755);
    mkdir($modulePath . '/views', 0755);
    echo CLI_GREEN, 'Basic folder architecture created for ', CLI_LIGHT_CYAN, substr($modulePath,
      strlen(BASE_PATH)), CLI_GREEN, '.', END_COLOR, PHP_EOL;
  }

  /**
   * @param bool   $interactive
   * @param string $bundleName
   * @param string $moduleName
   *
   * @throws \otra\OtraException
   */
  function moduleHandling(bool $interactive, string $bundleName, string $moduleName) : void
  {
    // This constant is already defined if we have created a bundle on the process via CheckModuleExistence.php
    if (!defined('BUNDLE_BASE_PATH'))
      define('BUNDLE_BASE_PATH', BASE_PATH . 'bundles/' . $bundleName . '/');

    if ($interactive)
    {
      while ($moduleName !== 'n')
      {
        createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
        $moduleName = promptUser('What is the name of the next module ? (type n to stop)');
      }
    } else
      createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
  }
}

