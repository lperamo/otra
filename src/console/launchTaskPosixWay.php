<?php
declare(strict_types=1);

namespace otra\console;

use otra\OtraException;
use const otra\cache\php\DIR_SEPARATOR;

/**
 *
 * @throws OtraException
 */
function launchTaskPosixWay(array $tasksClassMap, array $arguments, string $taskName) : void
{
  $paramsDesc = require $tasksClassMap[$taskName][TasksManager::TASK_CLASS_MAP_TASK_PATH] .
    DIR_SEPARATOR . $taskName . 'Help.php';

  $arguments = [
    0 => $arguments[0],
    1 => $taskName
  ];
  $optionalParams = $requiredParams = $paramsArray = [];
  $parameters = array_keys($paramsDesc[TasksManager::TASK_PARAMETERS]);
  $statuses = $paramsDesc[TasksManager::TASK_STATUS];

  foreach($parameters as $paramKey => $param)
  {
    $paramStringToAdd = $param;

    if ($statuses[$paramKey] === 'required')
    {
      $paramStringToAdd .= ':';
      $requiredParams[$paramKey]= $param;
    } else
    {
      $paramStringToAdd .= '::';
      $optionalParams[$paramKey] = $param;
    }

    $paramsArray[]= $paramStringToAdd;
  }

  $getoptArguments = getopt('', $paramsArray, $restIndex);

  foreach ($requiredParams as $parameter)
  {
    if (!isset($getoptArguments[$parameter]))
    {
      echo CLI_ERROR . 'The parameter ' . CLI_INFO_HIGHLIGHT . $parameter . CLI_ERROR . ' is required!' .
        END_COLOR . PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    // @codeCoverageIgnoreStart
    $arguments[]= $getoptArguments[$parameter]; // @code
  }


  foreach ($optionalParams as $parameter)
  {
    if (isset($getoptArguments[$parameter]))
    {
      $arguments[]= $getoptArguments[$parameter];
    }
  }

  // And we run the task if all is correct
  TasksManager::execute($tasksClassMap, $taskName, $arguments);
  // @codeCoverageIgnoreEnd
}
