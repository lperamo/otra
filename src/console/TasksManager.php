<?php
declare(strict_types=1);
namespace otra\console;

use otra\OtraException;
use function otra\console\deployment\genClassMap\genClassMap;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{BASE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\deployment\updateConf\{UPDATE_CONF_ARG_MASK, UPDATE_CONF_ARG_ROUTE_NAME};

/**
 * @author Lionel Péramo
 * @package otra
 */
abstract class TasksManager
{
  final public const int
    STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING = 40,
    PAD_LENGTH_FOR_TASK_TITLE_FORMATTING = 27,
    PAD_LENGTH_FOR_TASK_OPTION_FORMATTING = 22,
    TASK_CLASS_MAP_TASK_PATH = 0,
    TASK_CLASS_MAP_TASK_PARAMETERS = 1,
    TASK_DESCRIPTION = 0,
    TASK_NAME = 1,
    TASK_PARAMETERS = 1,
    TASK_STATUS = 2,
    TASK_CATEGORY = 3;

  final public const string
    REQUIRED_PARAMETER = 'required',
    OPTIONAL_PARAMETER = 'optional';

  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands(string $message) : void
  {
    define(__NAMESPACE__ . '\\HELP_BETWEEN_TASK_AND_COLON', 28);
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
   *
   * @throws OtraException
   */
  public static function execute(array $tasksClassMap, string $otraTask, array $argumentsVector) : void
  {
    // If the class map does not exist yet, we create it and load it
    if (!file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
    {
      echo CLI_WARNING,
        'We cannot use the console if the class mapping files do not exist ! We launch the generation of those files ...',
        END_COLOR, PHP_EOL;
      require $tasksClassMap['genClassMap'][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/genClassMapTask.php';
      genClassMap([]);

      // If the task was genClassMap...then we have nothing left to do!
      if ($otraTask === 'genClassMap')
        throw new OtraException(exit: true);

      require_once CACHE_PHP_INIT_PATH . 'ClassMap.php';
    }

    // _once as otherwise we cannot do multiple tasks in a row
    $taskPath = $tasksClassMap[$otraTask][TasksManager::TASK_CLASS_MAP_TASK_PATH];
    require_once $taskPath . DIR_SEPARATOR . $otraTask . 'Task.php';

    $otraTaskFull = (str_contains($taskPath, CORE_PATH)
      ? 'otra\\' . str_replace(
        [CORE_PATH, '/'],
        ['', '\\'],
        $taskPath
      )
      : str_replace(
        [BASE_PATH, '/'],
        ['', '\\'],
        $taskPath
      )) . '\\' . $otraTask;

    if  ($otraTask === 'updateConf')
      $otraTaskFull(
        $argumentsVector[UPDATE_CONF_ARG_MASK] ?? null,
        $argumentsVector[UPDATE_CONF_ARG_ROUTE_NAME] ?? null
      );
    else
      ($tasksClassMap[$otraTask][TasksManager::TASK_CLASS_MAP_TASK_PARAMETERS] === [])
        ? $otraTaskFull()
        : $otraTaskFull($argumentsVector);
  }
}

if (!defined(__NAMESPACE__ . '\\STRING_PAD_FOR_OPTION_FORMATTING'))
  define(
    __NAMESPACE__ . '\\STRING_PAD_FOR_OPTION_FORMATTING',
    str_repeat(' ', TasksManager::STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING)
  );
