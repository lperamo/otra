<?php

use otra\console\TasksManager;

define('HELP_TASK', 2);
$task = $argv[HELP_TASK];
$tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

if (false === isset($tasksClassMap[$task]))
{
  require CONSOLE_PATH . 'tools.php';
  list($newTask) = guessWords($task, array_keys($tasksClassMap));

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo CLI_RED, 'There is no task named ', CLI_YELLOW, $task, CLI_RED, ' !', END_COLOR, PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('There is no task named ' . $task . ' ! Do you mean ' . CLI_WHITE . $newTask . CLI_YELLOW . ' ? (y/n)');

  if ('y' === $choice)
    $task = $newTask;
  else
  {
    echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
    exit(1);
  }
}

/** WE DISPLAY HERE THE COMMAND HELP */
$paramsDesc = require $tasksClassMap[$task][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $task . 'Help.php';
echo CLI_WHITE, str_pad($task, 27, ' '), CLI_LIGHT_GRAY, ': ', CLI_CYAN, $paramsDesc[TasksManager::TASK_DESCRIPTION], PHP_EOL;

// If we have parameters for this command, displays them
if (isset($paramsDesc[TasksManager::TASK_PARAMETERS]) === true)
{
  $i = 0;

  foreach ($paramsDesc[TasksManager::TASK_PARAMETERS] as $parameter => &$paramDesc)
  {
    // + parameter : (required|optional) Description
    echo CLI_LIGHT_CYAN, '   + ', str_pad($parameter, 22, ' '), CLI_LIGHT_GRAY;
    echo ': ', CLI_LIGHT_CYAN, '(', $paramsDesc[TasksManager::TASK_STATUS][$i], ') ', CLI_CYAN, $paramDesc, PHP_EOL;
    ++$i;
  }
}

echo END_COLOR;
?>
