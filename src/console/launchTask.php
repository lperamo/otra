<?php
declare(strict_types=1);

namespace otra\console;

use otra\OtraException;
use const otra\cache\php\DIR_SEPARATOR;

/**
 * Launch a task if there is a description for it and if the parameters are correctly set.
 *
 *
 * @throws OtraException
 */
function launchTask(array $tasksClassMap, array $arguments, int $argumentsCount, string $taskName): void
{
  $paramsDesc = require $tasksClassMap[$taskName][TasksManager::TASK_CLASS_MAP_TASK_PATH] .
    DIR_SEPARATOR . $taskName . 'Help.php';

  // We check if the number of parameters is correct
  $total = $required = 0;

  if (isset($paramsDesc[TasksManager::TASK_STATUS]))
  {
    $result = array_count_values($paramsDesc[TasksManager::TASK_STATUS]);

    // Retrieves the number of required parameters
    if (isset($result['required']))
      $required = $result['required'];

    // Retrieves the number of required parameters and then the final total of parameters
    $total = $required + ($result['optional'] ?? 0);
  }

  if ($argumentsCount > $total + 2)
  {
    echo CLI_ERROR . 'There are too much parameters ! The total number of existing parameters is : ' . $total
      . END_COLOR . PHP_EOL . PHP_EOL;
    TasksManager::execute($tasksClassMap, 'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $arguments[1]]);
    throw new OtraException(code: 1, exit: true);
  }

  if ($argumentsCount < $required + 2)
  {
    echo CLI_ERROR . 'Not enough parameters ! The total number of required parameters is : ' . $required . END_COLOR
      . PHP_EOL . PHP_EOL;
    TasksManager::execute($tasksClassMap, 'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $arguments[1]]);
    throw new OtraException(code: 1, exit: true);
  }

  // And we run the task if all is correct
  TasksManager::execute($tasksClassMap, $taskName, $arguments);
}
