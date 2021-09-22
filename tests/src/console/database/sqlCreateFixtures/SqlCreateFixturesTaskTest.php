<?php
declare(strict_types=1);

namespace src\console\database\sqlCreateFixtures;

use otra\OtraException;
use otra\bdd\Sql;
use otra\config\AllConfig;
use otra\console\database\Database;
use otra\console\TasksManager;
use phpunit\framework\TestCase;
use ReflectionException;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, PROD, TEST_PATH};
use function otra\tools\{cleanFileAndFolders, copyFileAndFolders, removeFieldScopeProtection, setScopeProtectedFields};
use const otra\console\
{CLI_BASE, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class SqlCreateFixturesTaskTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CREATE_FIXTURES = 'sqlCreateFixtures',
    ERASE_AND_CLEAN = 2,
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_CONNECTION = 'test',
    DATABASE_NAME = 'testDB',
    SCHEMA_FILE = 'schema.yml',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    OTRA_LABEL_FIXTURES_FOLDER = 'fixtures/',
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER_YML_FIXTURES = self::CONFIG_FOLDER_YML . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    CONFIG_FOLDER_YML_FIXTURES_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE = 'tablesOrderFile',
    TABLES_ORDER_FILE = 'tables_order.yml',
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES = 'pathSqlFixtures',
    OTRA_VARIABLE_DATABASE_PATH_SQL = 'pathSql',
    CONFIG_FOLDER_SQL_FIXTURES = self::CONFIG_FOLDER_SQL . self::OTRA_LABEL_FIXTURES_FOLDER,
    OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES = 'pathYmlFixtures',
    SCHEMA_FILE_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::SCHEMA_FILE,
    TABLES_ORDER = ['testDB_table2', 'testDB_table3', 'testDB_table'],
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_SQL_FIXTURES_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES = self::CONFIG_FOLDER_SQL . 'truncate/',
    CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . 'truncate/';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    require CORE_PATH . 'tools/cleanFilesAndFolders.php';

    cleanFileAndFolders([BASE_PATH . 'logs']);
  }

  /**
   * @throws OtraException
   */
  protected function tearDown(): void
  {
    parent::tearDown();

    cleanFileAndFolders([
      self::CONFIG_FOLDER_SQL,
      self::CONFIG_FOLDER_YML
    ]);

    require_once(self::TEST_CONFIG_GOOD_PATH);

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::DATABASE_NAME . '`;');
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   * @throws ReflectionException
   *
   * we use "Depends" and not "depends" (note the uppercase letter) as it does not work with "depends"
   * @Depends src\console\DataBaseTest::testCreateFixtures
   */
  public function testCleanAndErase() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    removeFieldScopeProtection(Database::class, 'boolSchema')->setValue(false);
    removeFieldScopeProtection(Database::class, 'folder')->setValue('tests/src/bundles/');
    mkdir(BUNDLES_PATH);

    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    define(__NAMESPACE__ . '\\VERBOSE', 2);
    copyFileAndFolders(
      [
        self::SCHEMA_FILE_BACKUP,
        self::CONFIG_FOLDER_YML_BACKUP . self::TABLES_ORDER_FILE,
        self::CONFIG_FOLDER_YML_FIXTURES_BACKUP
      ],
      [
        self::SCHEMA_ABSOLUTE_PATH,
        self::TABLES_ORDER_FILE_PATH,
        self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    require(self::TEST_CONFIG_GOOD_PATH);

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];

    ob_start();
    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );
    ob_end_clean();

    ob_start();
    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_SQL_CREATE_FIXTURES,
      ['otra.php', self::OTRA_TASK_SQL_CREATE_FIXTURES, self::DATABASE_NAME, self::ERASE_AND_CLEAN]
    );

    // testing
    self::assertEquals(
      CLI_BASE . 'Fixtures sql files cleaned'. CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL . PHP_EOL .
      CLI_INFO_HIGHLIGHT . CLI_INFO_HIGHLIGHT . 'testDB.testDB_table2' . END_COLOR . PHP_EOL .
      'Table ' . CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR . CLI_SUCCESS . '[TRUNCATED]' . END_COLOR .  PHP_EOL .
      'Data  '. CLI_SUCCESS . '[YML IDENTIFIERS] ' . END_COLOR . CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR .
      CLI_SUCCESS . '[SQL EXECUTION]' . END_COLOR .  PHP_EOL . PHP_EOL .
      CLI_INFO . CLI_INFO_HIGHLIGHT . 'testDB.testDB_table3' . END_COLOR . PHP_EOL .
      'Table '. CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR . CLI_SUCCESS . '[TRUNCATED]' . END_COLOR .  PHP_EOL .
      'Data  '. CLI_SUCCESS . '[YML IDENTIFIERS] ' . END_COLOR . CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR .
      CLI_SUCCESS . '[SQL EXECUTION]' . END_COLOR . PHP_EOL . PHP_EOL .
      CLI_INFO_HIGHLIGHT . CLI_INFO_HIGHLIGHT . 'testDB.testDB_table' . END_COLOR . PHP_EOL .
      'Table '. CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR . CLI_SUCCESS . '[TRUNCATED]' . END_COLOR .  PHP_EOL .
      'Data  '. CLI_SUCCESS . '[YML IDENTIFIERS] ' . END_COLOR . CLI_SUCCESS . '[SQL CREATION] ' . END_COLOR .
      CLI_SUCCESS . '[SQL EXECUTION]' . END_COLOR .  PHP_EOL .
      END_COLOR,
      ob_get_clean()
    );

    foreach(self::TABLES_ORDER as $table)
    {
      // Fixtures creation files
      self::assertFileExists(self::CONFIG_FOLDER_SQL_FIXTURES . self::DATABASE_NAME . '_' . $table . '.sql');
      self::assertFileEquals(
        self::CONFIG_FOLDER_SQL_FIXTURES_BACKUP . self::DATABASE_NAME . '_' . $table . '.sql',
        self::CONFIG_FOLDER_SQL_FIXTURES . self::DATABASE_NAME . '_' . $table . '.sql');

      // Fixtures tables truncation files
      self::assertFileExists(self::CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES . self::DATABASE_NAME . '_' . $table . '.sql');
      self::assertFileEquals(
        self::CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES_BACKUP . self::DATABASE_NAME . '_' . $table . '.sql'
        ,self::CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES . self::DATABASE_NAME . '_' . $table . '.sql');
    }
  }
}
