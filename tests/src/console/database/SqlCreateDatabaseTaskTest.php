<?php
declare(strict_types=1);

namespace src\console\database;

use config\AllConfig;
use otra\console\Database;
use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SqlCreateDatabaseTaskTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    OTRA_TASK_HELP = 'help',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_NAME = 'testDB',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    SCHEMA_FILE = 'schema.yml',
    SCHEMA_FILE_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/' . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . 'tables_order.yml';

  /**
   * @throws \ReflectionException
   * @throws \otra\OtraException
   */
  public function testSqlCreateDatabaseTask() : void
  {
    // context
    $_SERVER[APP_ENV] = 'prod';
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );
    require(self::TEST_CONFIG_GOOD_PATH);

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];

    setScopeProtectedFields(
      Database::class,
      [
        'boolSchema' => false,
        'folder' => 'tests/src/bundles/',
        'pathSql' => self::CONFIG_FOLDER_SQL,
        'schemaFile' => self::SCHEMA_ABSOLUTE_PATH,
        'tablesOrderFile' => self::TABLES_ORDER_FILE_PATH
      ]
    );

    // launching
    TasksManager::execute(
      require BASE_PATH . 'cache/php/tasksClassMap.php',
      self::OTRA_TASK_SQL_CREATE_DATABASE,
      ['otra.php', self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME, 'true']
    );

    // Testing
    $endPath = removeFieldScopeProtection(Database::class, 'databaseFile')->getValue() . '_force.sql';
    self::assertFileEquals(
      self::CONFIG_FOLDER_SQL_BACKUP . $endPath,
      self::CONFIG_FOLDER_SQL . $endPath
    );
  }

  public function testSqlCreateDatabaseHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_SQL_CREATE_DATABASE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Database creation, tables creation.(sql_generate_basic)' .
      PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('databaseName', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_CYAN . 'The database name !' .
      PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('force', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_CYAN . 'If true, we erase the database !' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require BASE_PATH . 'cache/php/tasksClassMap.php',
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_CREATE_DATABASE]
    );
  }
}
