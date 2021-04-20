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
      CLI_BASE .
      str_pad(self::OTRA_TASK_HELP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Shows the extended help for the specified command.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('command', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The command which you need help for.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_HELP]
    );
  }
}
