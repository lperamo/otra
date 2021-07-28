<?php
declare(strict_types=1);

namespace src\console\database\sqlClean;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class SqlCleanHelpTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CLEAN = 'sqlClean',
    OTRA_TASK_HELP = 'help';

  /**
   * @throws OtraException
   */
  public function testSqlCleanHelp()
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_CLEAN, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Removes sql and yml files in the case where there are problems that had corrupted files.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('cleaningLevel', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Type 1 in order to also remove the file that describes the tables order.' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_CLEAN]
    );
  }
}
