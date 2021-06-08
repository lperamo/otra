<?php
declare(strict_types=1);

namespace src\console\helpAndTools\help;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV,PROD};
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class HelpTaskTest extends TestCase
{
  private const
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testHelpHelp() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;

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
