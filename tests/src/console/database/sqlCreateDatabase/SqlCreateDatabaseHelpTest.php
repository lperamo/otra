<?php
declare(strict_types=1);

namespace src\console\database\sqlCreateDatabase;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class SqlCreateDatabaseHelpTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    OTRA_TASK_HELP = 'help';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  public function testSqlCreateDatabaseHelp(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_CREATE_DATABASE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Database creation, tables creation.(sql_generate_basic)' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('database-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The database name !' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('force', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If true, we erase the database !' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_CREATE_DATABASE]
    );
  }
}
