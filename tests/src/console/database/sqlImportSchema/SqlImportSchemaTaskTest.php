<?php
declare(strict_types=1);

namespace src\console\database\sqlImportSchema;

use otra\config\AllConfig;
use otra\console\database\Database;
use otra\OtraException;
use otra\Session;
use phpunit\framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\cache\php\{APP_ENV, CONSOLE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use function otra\console\database\sqlCreateDatabase\sqlCreateDatabase;
use function otra\console\database\sqlImportSchema\sqlImportSchema;
use function otra\tools\{copyFileAndFolders, setScopeProtectedFields};

/**
 * @runTestsInSeparateProcesses
 */
class SqlImportSchemaTaskTest extends TestCase
{
  private const
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    DATABASE_CONNECTION = 'test',
    DATABASE_NAME = 'testDB',
    IMPORTED_SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . 'importedSchema.yml',
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    OTRA_VARIABLE_DATABASE_PATH_SQL = 'pathSql',
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE = 'tablesOrderFile',
    SCHEMA_FILE = 'schema.yml',
    SCHEMA_FILE_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    TABLES_ORDER_FILE = 'tables_order.yml',
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

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
   */
  public function test() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders([self::SCHEMA_FILE_BACKUP], [self::SCHEMA_ABSOLUTE_PATH]);

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::DATABASE_CONNECTION);

    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    // we change the path to the schema.yml in order to not overwrite the existing one by precaution
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)
      ->setValue(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);

    // launching task
    require CONSOLE_PATH . 'database/sqlImportSchema/sqlImportSchemaTask.php';
    sqlImportSchema([self::DATABASE_NAME, self::DATABASE_CONNECTION]);
    self::assertFileExists(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);
    self::assertFileEquals(
      self::SCHEMA_FILE_BACKUP,
      self::IMPORTED_SCHEMA_ABSOLUTE_PATH,
      'Comparing ' . CLI_INFO_HIGHLIGHT . self::IMPORTED_SCHEMA_ABSOLUTE_PATH . CLI_ERROR . ' against ' .
      CLI_INFO_HIGHLIGHT . self::SCHEMA_FILE_BACKUP . CLI_ERROR
    );
  }
}
