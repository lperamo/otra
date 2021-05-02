<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModule;

use otra\OtraException;
use function otra\console\{architecture\createFolder,promptUser};
use const otra\console\{CLI_BASE,CLI_INFO_HIGHLIGHT,CLI_SUCCESS,END_COLOR};

require CONSOLE_PATH . 'architecture/createFolder.php';

if (!function_exists('createModule'))
{
  /**
   * @param string $bundleBasePath The path where we put modules
   * @param string $moduleName
   * @param bool   $interactive
   *
   * @throws OtraException
   */
  function createModule(string $bundleBasePath, string $moduleName, bool $interactive): void
  {
    $modulePath = $bundleBasePath . $moduleName;

    // If the folder does not exist and we are not in interactive mode, we exit the program.
    createFolder($modulePath, $bundleBasePath, 'module', $interactive);

    mkdir($modulePath . '/controllers', 0755);
    mkdir($modulePath . '/views', 0755);
    echo CLI_BASE, 'Basic folder architecture created for ', CLI_INFO_HIGHLIGHT, substr($modulePath,
      strlen(BASE_PATH)), CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
  }

  /**
   * @param bool   $interactive
   * @param string $bundleName
   * @param string $moduleName
   *
   * @throws OtraException
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

