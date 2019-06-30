<?
declare(strict_types=1);
require CORE_PATH . 'console\Tools.php';

if (false === isset($argv[2]))
{
  $argv[2] = promptUser('You did not specified a name for the bundle. What is it ?');

  // We clean the screen
  echo ERASE_SEQUENCE;
}

$bundlesPath = BASE_PATH . 'bundles/';

while (true === file_exists($bundlesPath . $argv[2]))
{
  // Erases the previous question before we ask...
  $argv[2] = promptUser('This folder \'' . $argv[2] . '\' already exist. Try once again :');

  // We clean the screen
  echo ERASE_SEQUENCE;
}

$folders = ['models', 'resources', 'views'];

if (false === isset($argv[3]) || $argv[3] < 0 || $argv[3] > 7)
{
  $begin = 'You don\'t have specified which directories you want to create or the mask is incorrect. Do you want to associate ';
  $end = ' with that bundle (0 or 1)?';
  $argv[3] = 0; // By default, we create 0 additional folders

  foreach($folders as $key => &$folder)
  {
    $answer = promptUser($begin . $folder . $end);

    while ('0' !== $answer && '1' !== $answer)
    {
      $answer = promptUser('Bad answer. ' . $begin . $folder . $end);
      // We clean the screen
      echo ERASE_SEQUENCE;
    }

    $argv[3] += pow(2, $key) * $answer;
  }
}

$moduleBasePath = $bundlesPath . $argv[2];
mkdir($moduleBasePath, 755);
echo ERASE_SEQUENCE, green(), 'Bundle \'', cyan(), $argv[2], greenText('\' created.'), PHP_EOL;

$mask = $argv[3];

foreach ($folders as $key => &$folder)
{
  // Checks if the folder have to be created or not.
  if ($mask & pow(2, $key))
  {
    mkdir($moduleBasePath . '/' . $folder, 755);
    echo green(), 'Folder \'', cyan(), $folder, greenText('\' created.'), PHP_EOL;
  }
}
?>
