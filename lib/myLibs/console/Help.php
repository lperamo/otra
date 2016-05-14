<?

$method = $argv[2];
$methods = get_class_methods('lib\myLibs\console\Tasks');

if (false === in_array($method, $methods, true))
{
  require CORE_PATH . 'console/ConsoleTools.php';
  list($newTask) = guessWords($method, $methods);

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo red(), 'There is no task named ', brown(), $method, redText(' !'), PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('There is no task named ' . $method . ' ! Do you mean ' . white() . $newTask . brown() . ' ? (y/n)');

  if ('y' === $choice)
    $method = $newTask;
  else
  {
    echo redText('Sorry then !'), PHP_EOL;
    exit(1);
  }
}

/** WE DISPLAY HERE THE COMMAND HELP */
$methodDesc = $method . 'Desc';
$paramsDesc = self::$methodDesc();
echo white(), str_pad($method, 25, ' '), lightGray(), ': ', cyan(), $paramsDesc[0], PHP_EOL;

// If we have parameters for this command, displays them
if (isset($paramsDesc[1]))
{
  $i = 0;
  foreach ($paramsDesc[1] as $parameter => $paramDesc)
  {
    // + parameter : (required|optional) Description
    echo lightCyan(), ' + ', str_pad($parameter, 22, ' '), lightGray();
    echo ': ', lightCyan(), '(', $paramsDesc[2][$i], ') ', cyan(), $paramDesc, PHP_EOL;
    ++$i;
  }
}

echo PHP_EOL, endColor();

?>
