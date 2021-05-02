<?php
declare(strict_types=1);
namespace otra\console\architecture\createBundle;
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

use otra\OtraException;
use function otra\console\promptUser;
use const otra\console\{CLI_BASE,CLI_INFO_HIGHLIGHT,CLI_SUCCESS,CLI_WARNING,END_COLOR};

const BUNDLE_FOLDERS = ['config', 'models', 'resources', 'views'];

const OTRA_BUNDLES_MAIN_FOLDER_NAME = 'bundles/';

/**
 * @param bool     $interactive
 * @param string   $bundleName
 * @param int|null $bundleMask
 * @param bool     $bundleTask
 *
 * @throws OtraException
 */
function bundleHandling(bool $interactive, string $bundleName, ?int $bundleMask, bool $bundleTask = false) : void
{
  $bundleName = ucfirst($bundleName);
  define('BUNDLE_ROOT_PATH', BASE_PATH . OTRA_BUNDLES_MAIN_FOLDER_NAME);
  $errorMessage = CLI_WARNING . 'The bundle ' . CLI_INFO_HIGHLIGHT . OTRA_BUNDLES_MAIN_FOLDER_NAME . $bundleName . CLI_WARNING . ' already exists.';

  if (!$interactive && file_exists(BUNDLE_ROOT_PATH . $bundleName))
  {
    echo $errorMessage, END_COLOR, PHP_EOL;

    /** @var bool $consoleForce */
    throw new OtraException('', 1, '', NULL, [], true);
  }

  while (file_exists(BUNDLE_ROOT_PATH . $bundleName))
  {
    // Erases the previous question before we ask...
    $bundleName = promptUser($errorMessage . ' Try another folder name (type n to stop):');

    if ($bundleName === 'n')
      throw new OtraException('', 0, '', NULL, [], true);

    $bundleName = ucfirst($bundleName);

    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  // Checking argument : folder mask
  if (null === $bundleMask
    || $bundleMask < 0
    || $bundleMask > pow(2, count(BUNDLE_FOLDERS)) - 1)
  {
    if ($bundleTask)
    {
      echo CLI_WARNING,
        (null === $bundleMask)
          ? 'You don\'t have specified which directories you want to create.'
          : 'The mask is incorrect.',
        PHP_EOL;
    }

    require CONSOLE_PATH . 'architecture/createBundle/bundleMaskCreation.php';
  }

  define('BUNDLE_BASE_PATH', BUNDLE_ROOT_PATH . $bundleName . '/');
  mkdir(BUNDLE_BASE_PATH, 0755, true);
  echo ERASE_SEQUENCE, CLI_BASE, 'Bundle ', CLI_INFO_HIGHLIGHT, OTRA_BUNDLES_MAIN_FOLDER_NAME, $bundleName, CLI_BASE,
    ' created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  define('BUNDLE_FOLDERS_MASK', $bundleMask);

  foreach (BUNDLE_FOLDERS as $numericKey => $folder)
  {
    // Checks if the folder have to be created or not.
    if (BUNDLE_FOLDERS_MASK & pow(2, $numericKey))
    {
      mkdir(BUNDLE_BASE_PATH . $folder, 0755);
      echo CLI_BASE, 'Folder ', CLI_INFO_HIGHLIGHT, $bundleName, '/', $folder, CLI_BASE, ' created',  CLI_SUCCESS, ' ✔',
        END_COLOR, PHP_EOL;
    }
  }
}

