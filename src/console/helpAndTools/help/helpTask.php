<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

use otra\console\TasksManager;
use otra\OtraException;

const HELP_TASK = 2;
$consoleTask = $argv[HELP_TASK];
$tasksClassMap = require CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

if (!isset($tasksClassMap[$consoleTask]))
{
  require CONSOLE_PATH . 'tools.php';
  [$newTask] = guessWords($consoleTask, array_keys($tasksClassMap));

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo CLI_ERROR, 'There is no task named ', CLI_WARNING, $consoleTask, CLI_ERROR, ' !', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('There is no task named ' . $consoleTask . ' ! Do you mean ' . CLI_BASE . $newTask .
    CLI_WARNING . ' ? (y/n)');

  if ('y' === $choice)
    $consoleTask = $newTask;
  else
  {
    echo CLI_ERROR, 'Sorry then !', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

/** WE DISPLAY HERE THE COMMAND HELP */
$paramsDesc = require $tasksClassMap[$consoleTask][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $consoleTask .
  'Help.php';
echo CLI_BASE, str_pad($consoleTask, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING),
  CLI_GRAY, ': ', CLI_INFO, $paramsDesc[TasksManager::TASK_DESCRIPTION], PHP_EOL;

// If we have parameters for this command, displays them
if (isset($paramsDesc[TasksManager::TASK_PARAMETERS]))
{
  $taskStatusParameterIndex = 0;

  foreach ($paramsDesc[TasksManager::TASK_PARAMETERS] as $parameter => $paramDesc)
  {
    // + parameter : (required|optional) Description
    echo CLI_INFO_HIGHLIGHT, '   + ', str_pad($parameter, TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING);
    echo CLI_GRAY, ': ', CLI_INFO_HIGHLIGHT, '(', $paramsDesc[TasksManager::TASK_STATUS][$taskStatusParameterIndex],
      ') ', CLI_INFO, $paramDesc, PHP_EOL;
    ++$taskStatusParameterIndex;
  }
}

echo END_COLOR;

