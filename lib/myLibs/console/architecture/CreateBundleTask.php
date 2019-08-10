<?
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';

const ARG_BUNDLE_NAME = 2;
const ARG_MASK = 3;

// Checking argument : bundle name
if (false === isset($argv[ARG_BUNDLE_NAME]))
{
  $bundleName = promptUser('You did not specified a name for the bundle. What is it ?');

  // We clean the screen
  echo ERASE_SEQUENCE;
} else {
  $bundleName = $argv[ARG_BUNDLE_NAME];
}

$bundleName = ucfirst($bundleName);
define('BUNDLE_ROOT_PATH', BASE_PATH . 'bundles/');

while (true === file_exists(BUNDLE_ROOT_PATH . $bundleName))
{
  // Erases the previous question before we ask...
  $bundleName = promptUser('The bundle ' . CLI_LIGHT_CYAN . $bundleName . CLI_BROWN . ' already exist. Try another folder name (type n to stop):');

  if ($bundleName === 'n')
    exit(0);

  $bundleName = ucfirst($bundleName);

  // We clean the screen
  echo ERASE_SEQUENCE;
}

const BUNDLE_FOLDERS = ['config', 'models', 'resources', 'views'];

// Checking argument : folder mask
if (false === isset($argv[ARG_MASK])
  || $argv[ARG_MASK] < 0
  || $argv[ARG_MASK] > pow(2, count(BUNDLE_FOLDERS)) - 1
  || is_numeric($argv[ARG_MASK]) === false
)
{
  echo CLI_BROWN,
    (false === isset($argv[ARG_MASK]))
      ? 'You don\'t have specified which directories you want to create.'
      : 'The mask is incorrect.',
     PHP_EOL;

  require CORE_PATH . 'console/architecture/BundleMaskCreation.php';
}

define('BUNDLE_BASE_PATH', BUNDLE_ROOT_PATH . $bundleName . '/');
mkdir(BUNDLE_BASE_PATH, 0755);
echo ERASE_SEQUENCE, CLI_GREEN, 'Bundle ', CLI_CYAN, $bundleName, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;

define('BUNDLE_FOLDERS_MASK', $argv[ARG_MASK]);

foreach (BUNDLE_FOLDERS as $key => &$folder)
{
  // Checks if the folder have to be created or not.
  if (BUNDLE_FOLDERS_MASK & pow(2, $key))
  {
    mkdir(BUNDLE_BASE_PATH . $folder, 0755);
    echo CLI_GREEN, 'Folder ', CLI_CYAN, $folder, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;
  }
}
?>
