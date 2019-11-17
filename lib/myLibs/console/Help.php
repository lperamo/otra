<?php
$method = $argv[2];
$methods = get_class_methods('lib\myLibs\console\Tasks');

if (false === in_array($method, $methods, true))
{
  require CORE_PATH . 'console/Tools.php';
  list($newTask) = guessWords($method, $methods);

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo CLI_RED, 'There is no task named ', CLI_YELLOW, $method, CLI_RED, ' !', END_COLOR, PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('There is no task named ' . $method . ' ! Do you mean ' . CLI_WHITE . $newTask . CLI_YELLOW . ' ? (y/n)');

  if ('y' === $choice)
    $method = $newTask;
  else
  {
    echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
    exit(1);
  }
}

/** WE DISPLAY HERE THE COMMAND HELP */
$methodDesc = $method . 'Desc';
$paramsDesc = \lib\myLibs\console\Tasks::$methodDesc();
echo CLI_WHITE, str_pad($method, 27, ' '), CLI_LIGHT_GRAY, ': ', CLI_CYAN, $paramsDesc[TASK_DESCRIPTION], PHP_EOL;

// If we have parameters for this command, displays them
if (isset($paramsDesc[TASK_PARAMETERS]) === true)
{
  $i = 0;

  foreach ($paramsDesc[TASK_PARAMETERS] as $parameter => &$paramDesc)
  {
    // + parameter : (required|optional) Description
    echo CLI_LIGHT_CYAN, '   + ', str_pad($parameter, 22, ' '), CLI_LIGHT_GRAY;
    echo ': ', CLI_LIGHT_CYAN, '(', $paramsDesc[TASK_STATUS][$i], ') ', CLI_CYAN, $paramDesc, PHP_EOL;
    ++$i;
  }
}

echo END_COLOR;

?>
