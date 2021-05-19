<?php
declare(strict_types=1);
namespace otra\console;

use otra\OtraException;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\DIR_SEPARATOR;

/**
 * @author Lionel Péramo
 * @package otra
 */
abstract class TasksManager
{
  public const
    STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING = 40,
    PAD_LENGTH_FOR_TASK_TITLE_FORMATTING = 27,
    PAD_LENGTH_FOR_TASK_OPTION_FORMATTING = 22,
    TASK_CLASS_MAP_TASK_PATH = 0,
    TASK_DESCRIPTION = 0,
    TASK_PARAMETERS = 1,
    TASK_STATUS = 2,
    TASK_CATEGORY = 3,
    REQUIRED_PARAMETER = 'required',
    OPTIONAL_PARAMETER = 'optional';

  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands(string $message) : void
  {
    define('otra\console\HELP_BETWEEN_TASK_AND_COLON', 28);
    echo PHP_EOL, CLI_WARNING, $message, CLI_BASE, PHP_EOL, PHP_EOL;
    echo 'The available commands are : ', PHP_EOL . PHP_EOL, '  - ', CLI_BASE,
      str_pad('no argument', HELP_BETWEEN_TASK_AND_COLON),
      CLI_GRAY;
    echo ': ', CLI_INFO, 'Shows the available commands.', PHP_EOL;
    /** @var array<string, array<int,array>> $methods */
    $methods = require CACHE_PHP_INIT_PATH . 'tasksHelp.php';
    $category = '';

    foreach ($methods as $method => $paramsDesc)
    {
      if (isset($paramsDesc[self::TASK_CATEGORY]))
      {
        if ($category !== $paramsDesc[self::TASK_CATEGORY])
        {
          $category = $paramsDesc[self::TASK_CATEGORY];
          echo CLI_INFO_HIGHLIGHT, PHP_EOL, '*** ', $category, ' ***', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;
        }
      } else
      {
        $category = 'Other';
        echo CLI_INFO_HIGHLIGHT, PHP_EOL, '*** ', $category, ' ***', PHP_EOL, PHP_EOL;
      }

      echo CLI_GRAY, '  - ', CLI_BASE, str_pad($method, HELP_BETWEEN_TASK_AND_COLON), CLI_GRAY, ': ',
        CLI_INFO, $paramsDesc[self::TASK_DESCRIPTION], PHP_EOL;
    }

    echo END_COLOR;
  }

  /**
   * @param array  $tasksClassMap
   * @param string $task
   * @param array  $argv
   *
   * @throws OtraException
   */
  public static function execute(array $tasksClassMap, string $task, array $argv) : void
  {
    if (!file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
    {
      echo CLI_WARNING,
        'We cannot use the console if the class mapping files do not exist ! We launch the generation of those files ...',
        END_COLOR, PHP_EOL;
      require $tasksClassMap['genClassMap'][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/genClassMapTask.php';

      // If the task was genClassMap...then we have nothing left to do !
      if ($task === 'genClassMap')
        throw new OtraException('', 0, '', NULL, [], true);
    }

    require_once CACHE_PHP_INIT_PATH . 'ClassMap.php';
    require $tasksClassMap[$task][TasksManager::TASK_CLASS_MAP_TASK_PATH] . DIR_SEPARATOR . $task . 'Task.php';
  }
}

if (!defined('otra\console\STRING_PAD_FOR_OPTION_FORMATTING'))
  define(
    'otra\console\STRING_PAD_FOR_OPTION_FORMATTING',
    str_repeat(' ', TasksManager::STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING)
  );

