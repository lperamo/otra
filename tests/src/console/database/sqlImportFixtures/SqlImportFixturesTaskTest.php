<?php
declare(strict_types=1);

namespace src\console\database\sqlImportFixtures;

use otra\config\AllConfig;
use otra\console\database\Database;
use otra\OtraException;
use otra\Session;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use function otra\console\database\sqlCreateDatabase\sqlCreateDatabase;
use function otra\console\database\sqlCreateFixtures\sqlCreateFixtures;
use function otra\console\database\sqlImportFixtures\sqlImportFixtures;
use function otra\tools\{copyFileAndFolders, setScopeProtectedFields};

/**
 * @runTestsInSeparateProcesses
 */
class SqlImportFixturesTaskTest extends TestCase
{
  private const
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_FIXTURES = self::CONFIG_FOLDER_SQL . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    CONFIG_FOLDER_YML_FIXTURES = self::CONFIG_FOLDER_YML . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_FIXTURES_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    DATABASE_CONNECTION = 'test',
    DATABASE_NAME = 'testDB',
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE = 'tablesOrderFile',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    OTRA_BINARY = 'otra.php',
    OTRA_LABEL_FIXTURES_FOLDER = 'fixtures/',
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    OTRA_TASK_SQL_CREATE_FIXTURES = 'sqlCreateFixtures',
    OTRA_TASK_SQL_IMPORT_FIXTURES = 'sqlImportFixtures',
    OTRA_VARIABLE_DATABASE_PATH_SQL = 'pathSql',
    OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES = 'pathSqlFixtures',
    OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES = 'pathYmlFixtures',
    SCHEMA_FILE = 'schema.yml',
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    TABLES_ORDER = ['testDB_table2', 'testDB_table3', 'testDB_table'],
    TABLES_ORDER_FILE = 'tables_order.yml',
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php';

  /**
   * Loads a main configuration specific to test purposes.
   */
  private function loadConfig() : void
  {
    require(self::TEST_CONFIG_GOOD_PATH);

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInit
   */
  public function test() : void
  {
    //context
    copyFileAndFolders(
      [
        self::CONFIG_FOLDER_YML_BACKUP
      ],
      [
        self::CONFIG_FOLDER_YML
      ]
    );

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();
    Database::init(self::DATABASE_CONNECTION);

    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateFixtures/sqlCreateFixturesTask.php';
    sqlCreateFixtures([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_FIXTURES, self::DATABASE_NAME, 1]);

    // restores correct content in the variable overwritten by the function call
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE)
      ->setValue(self::TABLES_ORDER_FILE_PATH);

    // launching the task
    require CONSOLE_PATH . 'database/sqlImportFixtures/sqlImportFixturesTask.php';
    sqlImportFixtures([self::OTRA_BINARY, self::OTRA_TASK_SQL_IMPORT_FIXTURES, self::DATABASE_NAME, self::DATABASE_CONNECTION]);

    foreach (self::TABLES_ORDER as $table)
    {
      $ymlFile = self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml';
      self::assertFileExists(self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml');
      self::assertFileEquals(self::CONFIG_FOLDER_YML_FIXTURES_BACKUP . $table . '.yml', $ymlFile);
    }
  }
}
