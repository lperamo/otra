<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\gpt\gptInstructions;

use otra\console\TasksManager;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{BASE_PATH, DIR_SEPARATOR};
use const otra\console\CLI_INFO_HIGHLIGHT;

/**
 * @return void
 */
function gptInstructions() : void
{
  echo 'Generates CLI commands list for the GPT \'OTRA Mentor\'.' , PHP_EOL;
  $content = '';
  $tasksClassMap = require CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

  $replacements = [
    '@\s{2,}@', // For multiple spaces
    '@\x1b\[38;2;(\d+;\d+;\d+)m@', // For ANSI sequences
    '@\x1b\[0m@' // For the reset ANSI sequence
  ];

  foreach ($tasksClassMap as $taskName => $taskClassMap)
  {
    $paramsDesc = require $taskClassMap[TasksManager::TASK_CLASS_MAP_TASK_PATH] . DIR_SEPARATOR .
      $taskName . 'Help.php';
    $content .= '*' . $taskName . '* : ' .
      preg_replace($replacements, [' ', ''], $paramsDesc[TasksManager::TASK_DESCRIPTION]) . PHP_EOL;

    if ($taskName === 'createGlobalConstants')
      echo $paramsDesc[TasksManager::TASK_DESCRIPTION], PHP_EOL, bin2hex($paramsDesc[TasksManager::TASK_DESCRIPTION]), PHP_EOL;

    // If we have parameters for this command, displays them
    if (isset($paramsDesc[TasksManager::TASK_PARAMETERS]))
    {
      $taskStatusParameterIndex = 0;

      foreach ($paramsDesc[TasksManager::TASK_PARAMETERS] as $parameter => $paramDesc)
      {
        // + parameter : (required|optional) Description
        $content .= '  - ' . $parameter;
        $content .= ' : ' . '(' . $paramsDesc[TasksManager::TASK_STATUS][$taskStatusParameterIndex] . ') ' .
          preg_replace($replacements, [' ', '', ''], $paramDesc) . PHP_EOL;
        ++$taskStatusParameterIndex;
      }
    }

    $content .= PHP_EOL;
  }

  file_put_contents(BASE_PATH . 'CLI commands list.txt', $content);
}
