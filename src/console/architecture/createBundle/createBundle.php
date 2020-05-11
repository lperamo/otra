<?php

const BUNDLE_FOLDERS = ['config', 'models', 'resources', 'views'];

/**
 * @param bool        $interactive
 * @param string      $bundleName
 * @param string|null $bundleMask
 * @param bool        $bundleTask
 *
 * @throws \otra\OtraException
 */
function bundleHandling(bool $interactive, string $bundleName, ?string $bundleMask, bool $bundleTask = false)
{
  $bundleName = ucfirst($bundleName);
  define('BUNDLE_ROOT_PATH', BASE_PATH . 'bundles/');
  $errorMessage = CLI_YELLOW . 'The bundle ' . CLI_LIGHT_CYAN . 'bundles/' . $bundleName . CLI_YELLOW . ' already exists.';

  if ($interactive === false)
  {
    if (file_exists(BUNDLE_ROOT_PATH . $bundleName))
    {
      echo $errorMessage, END_COLOR, PHP_EOL;

      /** @var bool $consoleForce */
      throw new \otra\OtraException('', 1, '', NULL, [], true);
    }
  }

  while (file_exists(BUNDLE_ROOT_PATH . $bundleName))
  {
    // Erases the previous question before we ask...
    $bundleName = promptUser($errorMessage . ' Try another folder name (type n to stop):');

    if ($bundleName === 'n')
      exit(0);

    $bundleName = ucfirst($bundleName);

    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  // Checking argument : folder mask
  if (null === $bundleMask
    || $bundleMask < 0
    || $bundleMask > pow(2, count(BUNDLE_FOLDERS)) - 1
    || is_numeric($bundleMask) === false)
  {
    if ($bundleTask === true)
    {
      echo CLI_YELLOW,
        (null === $bundleMask)
          ? 'You don\'t have specified which directories you want to create.'
          : 'The mask is incorrect.',
        PHP_EOL;
    }

    require CONSOLE_PATH . 'architecture/createBundle/bundleMaskCreation.php';
  }

  define('BUNDLE_BASE_PATH', BUNDLE_ROOT_PATH . $bundleName . '/');
  mkdir(BUNDLE_BASE_PATH, 0755, true);
  echo ERASE_SEQUENCE, CLI_GREEN, 'Bundle ', CLI_LIGHT_CYAN, 'bundles/', $bundleName, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;

  define('BUNDLE_FOLDERS_MASK', $bundleMask);

  foreach (BUNDLE_FOLDERS as $key => &$folder)
  {
    // Checks if the folder have to be created or not.
    if (BUNDLE_FOLDERS_MASK & pow(2, $key))
    {
      mkdir(BUNDLE_BASE_PATH . $folder, 0755);
      echo CLI_GREEN, 'Folder ', CLI_LIGHT_CYAN, $bundleName, '/', $folder, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;
    }
  }
}
?>
