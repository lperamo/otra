<?

if (false === isset($argv[2]))
  $argv[2] = promptUser('You did not specified a name for the bundle. What is it ?');

$bundlesPath = BASE_PATH . 'bundles/';
$eraseSequence = "\033[1A\r\033[K\e[1A\r\e[K";

while (true === file_exists($bundlesPath . $argv[2]))
{
  // Erases the previous question before we ask...
  $argv[2] = promptUser($eraseSequence . 'This folder \'' . $argv[2] . '\' already exist. Try once again :');
}

$folders = ['models', 'resources', 'views'];

if (false === isset($argv[3]))
{
  $begin = 'Do you want to associate ';
  $end = ' with that bundle (0 or 1)?';
  $argv[3] = 0; // By default, we create 0 additional folders

  foreach($folders as $key => &$folder)
  {
    $answer = promptUser($eraseSequence . $begin . $folder . $end);

    while ('0' !== $answer && '1' !== $answer)
    {
      $answer = promptUser($eraseSequence . 'Bad answer. ' . $begin . $folder . $end);
    }

    $argv[3] += pow(2, $key) * $answer;
  }
}

$moduleBasePath = $bundlesPath . $argv[2];
mkdir($moduleBasePath, 755);
echo $eraseSequence . green(), 'Bundle \'', cyan(), $argv[2], greenText('\' created.'), PHP_EOL;

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
