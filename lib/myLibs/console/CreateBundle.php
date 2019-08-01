<?
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';

const ARG_MASK = 3;

if (false === isset($argv[2]))
{
  $bundleName = promptUser('You did not specified a name for the bundle. What is it ?');

  // We clean the screen
  echo ERASE_SEQUENCE;
} else {
  $bundleName = $argv[2];
}

$bundlesRootPath = BASE_PATH . 'bundles/';

while (true === file_exists($bundlesRootPath . $bundleName))
{
  // Erases the previous question before we ask...
  $bundleName = promptUser('This folder ' . CLI_LIGHT_CYAN . $bundleName . CLI_BROWN. ' already exist. Try another folder name :');

  // We clean the screen
  echo ERASE_SEQUENCE;
}

$folders = ['config', 'models', 'resources', 'views'];

if (false === isset($argv[ARG_MASK])
  || $argv[ARG_MASK] < 0
  || $argv[ARG_MASK] > pow(2, count($folders)) - 1
)
{
  echo CLI_BROWN, 'You don\'t have specified which directories you want to create or the mask is incorrect.', PHP_EOL;
  $begin = 'Do you want to associate ';
  $end = ' with that bundle ' . END_COLOR . CLI_LIGHT_CYAN . $bundleName . CLI_BROWN . ' (n or y)?';
  $argv[ARG_MASK] = 0; // By default, we create 0 additional folders

  foreach($folders as $key => &$folder)
  {
    $question = $begin . CLI_LIGHT_CYAN . $folder . CLI_BROWN . $end;
    $answer = promptUser($question);

    while ('n' !== $answer && 'y' !== $answer)
    {
      $answer = promptUser('Bad answer. ' . $question);
      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    $argv[ARG_MASK] += pow(2, $key) * ($answer === 'y' ? 1 : 0);
  }
}

$bundleBasePath = $bundlesRootPath . $bundleName . '/';
mkdir($bundleBasePath, 0755);
echo ERASE_SEQUENCE, CLI_GREEN, 'Bundle ', CLI_CYAN, $bundleName, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;

$mask = $argv[ARG_MASK];

foreach ($folders as $key => &$folder)
{
  // Checks if the folder have to be created or not.
  if ($mask & pow(2, $key))
  {
    mkdir($bundleBasePath . $folder, 0755);
    echo CLI_GREEN, 'Folder ', CLI_CYAN, $folder, CLI_GREEN, ' created.', END_COLOR, PHP_EOL;
  }
}

?>
