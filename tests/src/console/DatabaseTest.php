<?php
declare(strict_types=1);

namespace src\console;

use config\AllConfig;
use PHPUnit\Framework\TestCase;
use otra\{OtraException, console\Database, bdd\Sql, Session};
use ReflectionException;

define('INIT_IMPORTS_FUNCTION', '_initImports');
define('OTRA_LABEL_FIXTURES_FOLDER', 'fixtures/');
define('OTRA_VARIABLE_DATABASE_BASE_DIRS', 'baseDirs');
define('OTRA_VARIABLE_DATABASE_PATH_SQL', 'pathSql');
define('OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES', 'pathSqlFixtures');
define('OTRA_VARIABLE_DATABASE_PATH_YML', 'pathYml');
define('OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES', 'pathYmlFixtures');
define('OTRA_VARIABLE_DATABASE_SCHEMA_FILE', 'schemaFile');
define('OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE', 'tablesOrderFile');

/**
 * @runTestsInSeparateProcesses
 */
class DatabaseTest extends TestCase
{
  private const
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_NAME = 'testDB',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    DATABASE_CONNECTION = 'test',
    DATABASE_FIRST_TABLE_NAME = 'testDB_table',
//    FIXTURES_FILE = 'db_fixture',
    SCHEMA_FILE = 'schema.yml',
    TABLES_ORDER_FILE = 'tables_order.yml',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_SQL_FIXTURES = self::CONFIG_FOLDER_SQL . OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_SQL_FIXTURES_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    CONFIG_FOLDER_YML_FIXTURES = self::CONFIG_FOLDER_YML . OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    CONFIG_FOLDER_YML_FIXTURES_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . OTRA_LABEL_FIXTURES_FOLDER,
    SCHEMA_FILE_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    IMPORTED_SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . 'importedSchema.yml',
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    TABLES_ORDER = ['testDB_table2', 'testDB_table3', 'testDB_table'];

  protected $preserveGlobalState = FALSE; // to fix some bugs like 'constant VERBOSE already defined

  /**
   * @throws ReflectionException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'prod';
    removeFieldScopeProtection(Database::class, 'boolSchema')->setValue(false);
    removeFieldScopeProtection(Database::class, 'folder')->setValue('tests/src/bundles/');
  }

  /**
   * @throws OtraException
   */
  public static function setUpBeforeClass() : void
  {
    parent::setUpBeforeClass();
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    require CORE_PATH . 'tools/cleanFilesAndFolders.php';
    require CORE_PATH . 'tools/debug/traceArray.php';

    cleanFileAndFolders([BASE_PATH . 'logs']);
  }

  /**
   * @throws OtraException
   */
  protected function tearDown(): void
  {
    parent::tearDown();
    $this->cleanAll();
  }

  /**
   * Clean files and the database that are created for tests.
   *
   * @throws OtraException
   */
  protected function cleanAll() : void
  {
    cleanFileAndFolders([
      self::CONFIG_FOLDER_SQL,
      self::CONFIG_FOLDER_YML
    ]);

    require_once(self::TEST_CONFIG_GOOD_PATH);

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::DATABASE_NAME . '`;');
  }

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
   * @throws ReflectionException|OtraException
   * @depends testGetDirs
   *
   * @author Lionel Péramo
   */
  public function testInitBase() : void
  {
    Database::initBase();

    // We test each private static variable that has been set by Database::initBase()
    self::assertEquals(
      Database::getDirs(),
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_BASE_DIRS)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_BASE_DIRS)->getValue()[0] . 'config/data/yml/',
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_YML)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_YML)->getValue() . OTRA_LABEL_FIXTURES_FOLDER,
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_BASE_DIRS)->getValue()[0] . 'config/data/sql/',
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue() . OTRA_LABEL_FIXTURES_FOLDER,
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_YML)->getValue() . self::SCHEMA_FILE,
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_SCHEMA_FILE)->getValue()
    );

    self::assertEquals(
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_YML)->getValue() . self::TABLES_ORDER_FILE,
      removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE)->getValue()
    );
  }

  /**
   * @throws OtraException
   *
   * TODO Put assertions and remove the related annotation!
   * @depends testInitBase
   * @doesNotPerformAssertions
   * @author                         Lionel Péramo
   */
  public function testInit() : void
  {
    $this->loadConfig();
    Database::init(self::DATABASE_CONNECTION);
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testGetDirs() : void
  {
    require self::TEST_CONFIG_GOOD_PATH;
    $dirs = Database::getDirs();
    self::assertIsArray($dirs);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * TODO add files before the test to test if they are cleaned
   *
   * @author Lionel Péramo
   */
  public function testClean() : void
  {
    // Creating the context
    copyFileAndFolders(
      [
        self::CONFIG_FOLDER_YML_BACKUP,
        self::CONFIG_FOLDER_SQL_BACKUP
      ],
      [
        self::CONFIG_FOLDER_YML,
        self::CONFIG_FOLDER_SQL
      ]
    );

    Database::clean();
    $sqlPath = removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue();
    self::assertEquals([], glob($sqlPath . '/*.sql'));
    self::assertEquals([], glob($sqlPath . 'truncate/*.sql'));
  }

  /**
   * @throws OtraException If the original YAML schema can't be copied.
   * @throws ReflectionException
   * depends testInit
   * depends testDropDatabase
   * @author Lionel Péramo
   */
  public function testCreateDatabase() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // Launching the task
    Database::createDatabase(self::DATABASE_NAME);

    // Assertions
    $endPath = removeFieldScopeProtection(Database::class, 'databaseFile')->getValue() . '.sql';
    self::assertFileEquals(
      self::CONFIG_FOLDER_SQL_BACKUP . $endPath,
      self::CONFIG_FOLDER_SQL . $endPath
    );
  }


  /**
   * @author Lionel Péramo
   * TODO Do a complete test, not just on the type
   */
  public function testGetAttr() : void
  {
    $attrTest = Database::getAttr('test');
    self::assertIsString($attrTest);
  }

  /**
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   */
  public function test_SortTableByForeignKeysEmpty() : void
  {
    $sortedTables = [];
    removeMethodScopeProtection(Database::class, '_sortTableByForeignKeys')
      ->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   */
  public function test_SortTableByForeignKeys() : void
  {
//    $_sortTableByForeignKeys = new ReflectionMethod(Database::class, '_sortTableByForeignKeys');
//    $_sortTableByForeignKeys->setAccessible(true);
//    $sortedTables = [];
//    $_sortTableByForeignKeys->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixture() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    // loading test configuration
    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    $sortedTables = [];
    Database::createFixture(
      self::DATABASE_NAME,
      self::DATABASE_FIRST_TABLE_NAME,
      [],
      [],
      [],
      $sortedTables,
      self::CONFIG_FOLDER_SQL_FIXTURES . self::DATABASE_NAME . '_' . self::DATABASE_FIRST_TABLE_NAME . '.sql'
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly_NoSchema() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::CONFIG_FOLDER_YML_FIXTURES_BACKUP],
      [self::CONFIG_FOLDER_YML_FIXTURES]
    );

    // loading test configuration
    $this->loadConfig();

    // Launching the task
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('You have to create a database schema file in config/data/' . self::SCHEMA_FILE . ' before using fixtures. Searching for : ');
    Database::createFixtures(self::DATABASE_NAME, 1);
  }


  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInit
   * @depends testCreateDatabase
   * @depends testTruncateTable
   * @depends testCreateFixture
   * @depends testExecuteFixture
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly() : void
  {
    // context
    define('VERBOSE', 2);
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

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    try
    {
      Database::createDatabase(self::DATABASE_NAME);
    } catch (OtraException $exception)
    {
      echo 'Schema already exists', PHP_EOL;
    }

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    // launching task
    Database::createFixtures(self::DATABASE_NAME, 1);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly_NoTablesOrderFile() : void
  {
    // context
    copyFileAndFolders(
      [
        self::SCHEMA_FILE_BACKUP,
        self::CONFIG_FOLDER_YML_FIXTURES_BACKUP
      ],
      [
        self::SCHEMA_ABSOLUTE_PATH,
        self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    // assertions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('You must use the database generation task before using the fixtures (no ' .
      substr(self::TABLES_ORDER_FILE_PATH, strlen(BASE_PATH)) . ' file)');

    // launching the task
    Database::createFixtures(self::DATABASE_NAME, 1);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixtures_CleanAndTruncate() : void
  {
    // context
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

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    // testing
    Database::createFixtures(self::DATABASE_NAME, 2);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testExecuteFile_DoesNotExist() : void
  {
    removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_SCHEMA_FILE)->setValue(self::SCHEMA_ABSOLUTE_PATH);

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('The file "blabla" does not exist !');
    Database::executeFile('blabla');
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   * @depends testInitBase
   * @depends testCreateDatabase
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testTruncateTable() : void
  {
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    removeFieldScopeProtection(Database::class, 'databaseFile');

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // Launching the tasks
    Database::createDatabase(self::DATABASE_NAME);
    Database::truncateTable(self::DATABASE_NAME, self::DATABASE_FIRST_TABLE_NAME);
  }

  /**
   * @author Lionel Péramo
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   */
  public function testExecuteFile_Exists() : void
  {
    echo __DIR__;
    //    Database::executeFile();
  }


  /**
   * @throws OtraException
   * @throws ReflectionException
   * @depends testInit
   *
   * @doesNotPerformAssertions
   *
   * TODO Modify the code that create the fixture, do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testExecuteFixture() : void
  {
    // context - copying the needed configuration files
    copyFileAndFolders(
      [
        self::SCHEMA_FILE_BACKUP,
        self::CONFIG_FOLDER_SQL_FIXTURES_BACKUP
      ],
      [
        self::SCHEMA_ABSOLUTE_PATH,
        self::CONFIG_FOLDER_SQL_FIXTURES
      ]
    );

    $this->loadConfig();

    // context - We create the database
    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );
    Database::createDatabase(self::DATABASE_NAME);

    removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES)->setValue(self::CONFIG_FOLDER_SQL_FIXTURES);

    // launching task
//    Database::createFixture(
//      self::self::DATABASE_NAME,
//      self::$databaseFirstTableName,
//      $fixturesData[self::TABLES_ORDER[0]],
//      $schema[self::$databaseFirstTableName],
//      self::TABLES_ORDER,
//      $fixturesMemory,
//      self::$configFolderSql . self::$fixturesFile . '/' . self::self::DATABASE_NAME . '_' . self::$databaseFirstTableName . '.sql'
//    );

//    removeMethodScopeProtection(Database::class, '_executeFixture')
//      ->invokeArgs(null, [self::self::DATABASE_NAME, self::TABLES_ORDER[0]]);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * TODO Do a complete test not just a type assertion
   * @author Lionel Péramo
   */
  public function testDropDatabase() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    define('VERBOSE', 2);

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // launching the task
    $sqlInstance = Database::dropDatabase(self::DATABASE_NAME);
    self::assertInstanceOf(Sql::class, $sqlInstance);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInitBase
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_NoSchema() : void
  {
    // Creating the context
    $this->loadConfig();

    Database::initBase();
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    // launching the task
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage("The file '" . substr(self::SCHEMA_ABSOLUTE_PATH, strlen(BASE_PATH)) . "' does not exist. We can't generate the SQL schema without it.");
    Database::generateSqlSchema(self::DATABASE_NAME);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInitBase
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_DontForce() : void
  {
    // Creating the context
    copyFileAndFolders([self::SCHEMA_FILE_BACKUP], [self::SCHEMA_ABSOLUTE_PATH]);

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);

    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::DATABASE_NAME);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInitBase
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_Force() : void
  {
    // Creating the context
    copyFileAndFolders([self::SCHEMA_FILE_BACKUP], [self::SCHEMA_ABSOLUTE_PATH]);

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);

    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::DATABASE_NAME, true);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * TODO Create a test fixture file in order to test that function !
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testAnalyzeFixtures() : void
  {
    // context
    copyFileAndFolders([self::CONFIG_FOLDER_YML_FIXTURES_BACKUP], [self::CONFIG_FOLDER_YML_FIXTURES]);

    // launching the task
    removeMethodScopeProtection(Database::class, '_analyzeFixtures')
      ->invokeArgs(null, [self::CONFIG_FOLDER_YML_FIXTURES . self::DATABASE_FIRST_TABLE_NAME . '.yml']);
  }

  /**
   * @throws ReflectionException
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   */
  public function testInitImports_AllNull() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    $confToUse = $database = null;

    // launching the task
    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Testing with $database = null
   *
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testInitImports_DatabaseNull() : void
  {
    // context
    $confToUse = self::DATABASE_CONNECTION;
    $database = null;

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage("The database 'testDB' does not exist.");

    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testInitImports_NoNull() : void
  {
    // context
    copyFileAndFolders([self::SCHEMA_FILE_BACKUP], [self::SCHEMA_ABSOLUTE_PATH]);

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // launching the task
    $confToUse = self::DATABASE_CONNECTION;
    $database = self::DATABASE_NAME;
    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Test with a non existent database.
   *
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testInitImports_BadDatabase() : void
  {
    // context
    $confToUse = self::DATABASE_CONNECTION;
    $database = 'noBDD';

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    // assertions about exceptions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage("The database 'noBDD' does not exist.");

    // launching task
    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testImportSchema() : void
  {
    // context
    copyFileAndFolders([self::SCHEMA_FILE_BACKUP], [self::SCHEMA_ABSOLUTE_PATH]);

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::DATABASE_CONNECTION);

    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // we change the path to the schema.yml in order to not overwrite the existing one by precaution
    removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_SCHEMA_FILE)->setValue(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);

    // launching task
    Database::importSchema(self::DATABASE_NAME, self::DATABASE_CONNECTION);
    self::assertFileExists(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);
    self::assertFileEquals(self::SCHEMA_FILE_BACKUP, self::IMPORTED_SCHEMA_ABSOLUTE_PATH);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInit
   *
   * @author Lionel Péramo
   */
  public function testImportFixtures() : void
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
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    Database::createFixtures(self::DATABASE_NAME, 1);

    // restores correct content in the variable overwritten by the function call
    removeFieldScopeProtection(Database::class, OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE)->setValue(self::TABLES_ORDER_FILE_PATH);

    // launching the task
    Database::importFixtures(self::DATABASE_NAME, self::DATABASE_CONNECTION);

    foreach (self::TABLES_ORDER as &$table)
    {
      $ymlFile = self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml';
      self::assertFileExists(self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml');
      self::assertFileEquals(self::CONFIG_FOLDER_YML_FIXTURES_BACKUP . $table . '.yml', $ymlFile);
    }
  }
}
