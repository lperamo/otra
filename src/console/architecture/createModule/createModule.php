<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModule;

use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};
use function otra\console\{architecture\createFolder,promptUser};

require CONSOLE_PATH . 'architecture/createFolder.php';

/**
 * @param string $bundleBasePath The path where we put modules
 * @param bool   $interactive    Do we allow questions to the user?
 * @param bool   $consoleForce   Determines whether we show an error when something is missing in non-interactive
 *                               mode or not. The false value by default will stop the execution if something does
 *                               not exist and show an error.
 * @throws OtraException
 */
function createModuleCore(string $bundleBasePath, string $moduleName, bool $interactive, bool $consoleForce): void
{
  $modulePath = $bundleBasePath . $moduleName;

  // If the folder does not exist, and we are not in interactive mode, we exit the program.
  createFolder($modulePath, $bundleBasePath, 'module', $interactive, $consoleForce);

  mkdir($modulePath . '/controllers', 0755);
  mkdir($modulePath . '/views', 0755);
  echo CLI_BASE, 'Basic folder architecture created for ', CLI_INFO_HIGHLIGHT, substr($modulePath,
    strlen(BASE_PATH)), CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
}

/**
 * @param bool   $interactive  Do we allow questions to the user?
 * @param bool   $consoleForce Determines whether we show an error when something is missing in non-interactive
 *                             mode or not. The false value by default will stop the execution if something does
 *                             not exist and show an error.
 * @param string $bundleName   The bundle in which the module has to be created
 *
 * @throws OtraException
 */
function moduleHandling(bool $interactive, bool $consoleForce, string $bundleName, string $moduleName) : void
{
  // This constant is already defined if we have created a bundle on the process via CheckModuleExistence.php
  if (!defined(__NAMESPACE__ . '\\BUNDLE_BASE_PATH'))
    define(__NAMESPACE__ . '\\BUNDLE_BASE_PATH', BUNDLES_PATH . $bundleName . DIR_SEPARATOR);

  if ($interactive)
  {
    while ($moduleName !== 'n')
    {
      createModuleCore(BUNDLE_BASE_PATH, $moduleName, true, $consoleForce);
      $moduleName = promptUser('What is the name of the next module ? (type n to stop)');
    }
  }
  else
    createModuleCore(BUNDLE_BASE_PATH, $moduleName, false, $consoleForce);
}
