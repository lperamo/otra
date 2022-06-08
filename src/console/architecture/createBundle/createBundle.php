<?php
declare(strict_types=1);
namespace otra\console\architecture\createBundle;
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
use otra\OtraException;
use function otra\console\promptUser;
use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE,CLI_INFO_HIGHLIGHT,CLI_SUCCESS,CLI_WARNING,ERASE_SEQUENCE,END_COLOR};

const
  BUNDLE_FOLDERS = ['config', 'models', 'resources', 'views'],
  OTRA_BUNDLES_MAIN_FOLDER_NAME = 'bundles/';

/**
 * @param bool     $interactive  Do we allow questions to the user?
 * @param bool     $consoleForce Determines whether we show an error when something is missing in non-interactive mode
 *                               or not. The false value by default will stop the execution if something does not exist
 *                               and shows an error.
 * @param string   $bundleName
 * @param int|null $bundleMask
 * @param bool     $bundleTask
 *
 * @throws OtraException
 */
function bundleHandling(
  bool $interactive,
  bool $consoleForce,
  string $bundleName,
  ?int $bundleMask,
  bool $bundleTask = false
): void
{
  $bundleName = ucfirst($bundleName);
  $errorMessage = CLI_WARNING . 'The bundle ' . CLI_INFO_HIGHLIGHT . OTRA_BUNDLES_MAIN_FOLDER_NAME . $bundleName .
    CLI_WARNING . ' already exists.';

  // && $bundleTask
  if (!$interactive && !$consoleForce && file_exists(BUNDLES_PATH . $bundleName))
  {
    echo ($bundleTask
      ? $errorMessage
      : CLI_WARNING . 'The bundle ' . CLI_INFO_HIGHLIGHT . OTRA_BUNDLES_MAIN_FOLDER_NAME . $bundleName . CLI_WARNING .
        ' does not exist.'),
      END_COLOR, PHP_EOL;

    throw new OtraException(code: 1, exit: true);
  }

  while (file_exists(BUNDLES_PATH . $bundleName))
  {
    // If the file does not exist and, we are not in interactive mode, we exit the program.
    if (!$interactive)
    {
      echo $errorMessage, PHP_EOL;
      break;
    }

    // Erases the previous question before we ask...
    $bundleName = promptUser($errorMessage . ' Try another folder name (type n to stop):');

    if ($bundleName === 'n')
      throw new OtraException(exit: true);

    $bundleName = ucfirst($bundleName);

    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  if ($interactive)
  {
    // Checking argument : folder mask
    if (null === $bundleMask
      || $bundleMask < 0
      || $bundleMask > (2 ** count(BUNDLE_FOLDERS)) - 1)
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
  } else
    $bundleMask ??= 15;

  define(__NAMESPACE__ . '\\BUNDLE_BASE_PATH', BUNDLES_PATH . $bundleName . DIR_SEPARATOR);

  if (!file_exists(BUNDLE_BASE_PATH))
    mkdir(BUNDLE_BASE_PATH, 0755, true);

  echo ERASE_SEQUENCE, CLI_BASE, 'Bundle ', CLI_INFO_HIGHLIGHT, OTRA_BUNDLES_MAIN_FOLDER_NAME, $bundleName, CLI_BASE,
    ' created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  define(__NAMESPACE__ . '\\BUNDLE_FOLDERS_MASK', $bundleMask);

  foreach (BUNDLE_FOLDERS as $numericKey => $folder)
  {
    // Checks if the folder have to be created or not.
    if (BUNDLE_FOLDERS_MASK & (2 ** $numericKey))
    {
      mkdir(BUNDLE_BASE_PATH . $folder, 0755);
      echo CLI_BASE, 'Folder ', CLI_INFO_HIGHLIGHT, $bundleName, DIR_SEPARATOR, $folder, CLI_BASE, ' created',  CLI_SUCCESS, ' ✔',
        END_COLOR, PHP_EOL;
    }
  }
}
