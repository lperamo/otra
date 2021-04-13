<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HelpTest extends TestCase
{
  private const
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   */
  public function testHelpHelp() : void
  {
    // context
    $_SERVER['APP_ENV'] = 'prod';

    // testing
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_HELP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Shows the extended help for the specified command.' .
      PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('command', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_CYAN . 'The command which you need help for.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require BASE_PATH . 'cache/php/init/tasksClassMap.php',
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_HELP]
    );
  }
}
