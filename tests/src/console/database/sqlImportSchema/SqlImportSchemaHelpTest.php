<?php
declare(strict_types=1);

namespace src\console\database\sqlImportSchema;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SqlImportSchemaHelpTest extends TestCase
{
  private const string
    OTRA_TASK_SQL_IMPORT_SCHEMA = 'sqlImportSchema',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_IMPORT_SCHEMA, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates the database schema from your database. (importSchema)' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('database-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The database name ! If not specified, we use the database specified in the configuration file.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('configuration', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The configuration that you want to use from your configuration file.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_IMPORT_SCHEMA]
    );
  }
}
