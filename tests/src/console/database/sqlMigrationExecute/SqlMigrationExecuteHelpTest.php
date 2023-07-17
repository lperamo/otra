<?php
declare(strict_types=1);

namespace src\console\database\sqlMigrationExecute;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class SqlMigrationExecuteHelpTest extends TestCase
{
  private const
    OTRA_TASK_SQL_MIGRATION_EXECUTE = 'sqlMigrationExecute',
    OTRA_TASK_HELP = 'help';
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_MIGRATION_EXECUTE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Execute a single migration version up or down manually.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('version', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO .
      'The migration version' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('way', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO .
      '\'up\' or \'down\'' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('connection', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'The database connection that you want to use from your configuration file' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_MIGRATION_EXECUTE]
    );
  }
}
