<?php
declare(strict_types=1);

namespace src\console;

use otra\config\AllConfig;
use PHPUnit\Framework\TestCase;
use otra\console\database\Database;
use otra\{OtraException, bdd\Sql, Session};
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use const otra\cache\php\{APP_ENV, BASE_PATH, CONSOLE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\database\sqlCreateDatabase\sqlCreateDatabase;
use function otra\console\database\sqlCreateFixtures\{analyzeFixtures, createFixture};
use function otra\tools\
{cleanFileAndFolders,
  copyFileAndFolders,
  removeFieldsScopeProtection,
  setScopeProtectedFields};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 *
 * @author Lionel Péramo
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
    SCHEMA_FILE = 'schema.yml',
    TABLES_ORDER_FILE = 'tables_order.yml',
    SQL_SCHEMA_FORCE_FILE = 'database_schema_force.sql',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_SQL_FIXTURES = self::CONFIG_FOLDER_SQL . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_SQL_FIXTURES_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH = self::CONFIG_FOLDER_SQL . self::SQL_SCHEMA_FORCE_FILE,
    SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . self::SQL_SCHEMA_FORCE_FILE,
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    CONFIG_FOLDER_YML_FIXTURES = self::CONFIG_FOLDER_YML . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    CONFIG_FOLDER_YML_FIXTURES_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    SCHEMA_FILE_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    INIT_IMPORTS_FUNCTION = '_initImports',
    OTRA_BINARY = 'otra.php',
    OTRA_LABEL_FIXTURES_FOLDER = 'fixtures/',
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    OTRA_VARIABLE_DATABASE_PATH_SQL = 'pathSql',
    OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES = 'pathSqlFixtures',
    OTRA_VARIABLE_DATABASE_PATH_YML = 'pathYml',
    OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES = 'pathYmlFixtures',
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE = 'tablesOrderFile';

  /**
   * @throws ReflectionException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
    $reflectedClass = (new ReflectionClass(Database::class));
    $reflectedClass->getProperty('folder')->setValue('tests/src/bundles/');
  }

  /**
   * @throws OtraException
   */
  public static function setUpBeforeClass() : void
  {
    parent::setUpBeforeClass();
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    require CORE_PATH . 'tools/cleanFilesAndFolders.php';

    foreach(glob(BASE_PATH . 'logs/**/**.txt') as $logFile)
    {
      file_put_contents($logFile, '');
    }
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

    require_once self::TEST_CONFIG_GOOD_PATH;

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::DATABASE_NAME . '`;');
  }

  /**
   * Loads a main configuration specific to test purposes.
   */
  private function loadConfig() : void
  {
    require self::TEST_CONFIG_GOOD_PATH;

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
  }

  /**
   * @throws ReflectionException
   */
  public function testInitBase() : void
  {
    // launching
    Database::initBase();

    // testing
    $reflectedClass = (new ReflectionClass(Database::class));
    $ymlPath = $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_YML)->getValue();
    $sqlPath = $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue();

    // We test each private static variable that has been set by Database::initBase()
    self::assertSame(
      $ymlPath . self::OTRA_LABEL_FIXTURES_FOLDER,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES)->getValue()
    );

    self::assertSame(
      $sqlPath . self::OTRA_LABEL_FIXTURES_FOLDER,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES)->getValue()
    );

    self::assertSame(
      $ymlPath . self::SCHEMA_FILE,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)->getValue()
    );

    self::assertSame(
      $ymlPath . self::TABLES_ORDER_FILE,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE)->getValue()
    );
  }

  /**
   * @depends testInitBase
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testInit() : void
  {
    // context
    $this->loadConfig();

    // launching
    Database::init(self::DATABASE_CONNECTION);
    $testKeys = ['base', 'motor', 'password', 'user', self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES, 'init'];

    // testing
    $unprotectedFields = removeFieldsScopeProtection(
      Database::class,
      $testKeys
    );

    $testValues = [
      'base' => self::DATABASE_NAME,
      'motor' => 'InnoDB',
      'password' => $_SERVER['TEST_PASSWORD'],
      'user' => $_SERVER['TEST_LOGIN'],
      'pathYmlFixtures' => self::CONFIG_FOLDER_YML_FIXTURES,
      'init' => true
    ];

    foreach ($unprotectedFields as $fieldNameKey => $unprotectedField)
    {
      if ($fieldNameKey === 'pathYmlFixtures')
        continue;

      self::assertSame(
        $testValues[$fieldNameKey],
        $unprotectedField->getValue(),
        'Testing the field ' . CLI_INFO_HIGHLIGHT . $testKeys[$fieldNameKey] . END_COLOR
      );
    }
  }

  public function testGetAttr() : void
  {
    $attrTest = Database::getAttr('test');
    self::assertIsString($attrTest);
  }

  /**
   * @throws ReflectionException
   * @doesNotPerformAssertions
   */
  public function test_SortTableByForeignKeysEmpty() : void
  {
    $sortedTables = [];
    (new ReflectionMethod(Database::class, '_sortTableByForeignKeys'))
      ->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @doesNotPerformAssertions
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
   * @doesNotPerformAssertions
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    $sortedTables = [];
    require CONSOLE_PATH . 'database/sqlCreateFixtures/sqlCreateFixturesTask.php';
    createFixture(
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
   * @throws ReflectionException
   * @depends testInitBase
   * @depends src\console\database\sqlCreateDatabase\SqlCreateDatabaseTaskTest::testSqlCreateDatabaseTask
   * @doesNotPerformAssertions
   */
  public function testTruncateTable() : void
  {
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    // Removes protection on this field
    (new ReflectionClass(Database::class))->getProperty('databaseFile');

    $this->loadConfig();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // Launching the tasks
    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);
    Database::truncateTable(self::DATABASE_NAME, self::DATABASE_FIRST_TABLE_NAME);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   * @depends testInit
   *
   * @doesNotPerformAssertions
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );
    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES)
      ->setValue(self::CONFIG_FOLDER_SQL_FIXTURES);

    // launching task
//    Database::createFixture(
//      self::DATABASE_NAME,
//      self::$databaseFirstTableName,
//      $fixturesData[self::TABLES_ORDER[0]],
//      $schema[self::$databaseFirstTableName],
//      self::TABLES_ORDER,
//      $fixturesMemory,
//      self::$configFolderSql . self::$fixturesFile . DIR_SEPARATOR . self::DATABASE_NAME . '_' . self::$databaseFirstTableName . '.sql'
//    );

//    removeMethodScopeProtection(Database::class, '_executeFixture')
//      ->invokeArgs(null, [self::DATABASE_NAME, self::TABLES_ORDER[0]]);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testDropDatabase() : void
  {
    // Creating the context
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );

    define(__NAMESPACE__ . '\\VERBOSE', 2);

    $this->loadConfig();

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

    // launching the task
    $sqlInstance = Database::dropDatabase(self::DATABASE_NAME);
    self::assertInstanceOf(Sql::class, $sqlInstance);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInitBase
   */
  public function testGenerateSqlSchema_NoSchema() : void
  {
    // Creating the context
    $this->loadConfig();

    Database::init();
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    // launching the task
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage("The file '" . substr(self::SCHEMA_ABSOLUTE_PATH, strlen(BASE_PATH)) .
      "' does not exist. We can't generate the SQL schema without it.");
    Database::generateSqlSchema(self::DATABASE_NAME);
  }

  /**
   * @depends testInitBase
   * @doesNotPerformAssertions
   * @throws OtraException
   * @throws ReflectionException
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::DATABASE_NAME);
  }

  /**
   * @depends testInitBase
   * @throws OtraException
   * @throws ReflectionException
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::DATABASE_NAME, true);

    self::assertFileExists(self::SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH);
    self::assertFileEquals(
      self::SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH_BACKUP,
      self::SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH,
      'Comparing ' . CLI_INFO_HIGHLIGHT . self::SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH . CLI_ERROR .
        CLI_INFO_HIGHLIGHT . ' against ' . self::SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH_BACKUP . CLI_ERROR
    );
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   */
  public function testAnalyzeFixtures() : void
  {
    // context
    copyFileAndFolders([self::CONFIG_FOLDER_YML_FIXTURES_BACKUP], [self::CONFIG_FOLDER_YML_FIXTURES]);
    require CONSOLE_PATH . 'database/sqlCreateFixtures/sqlCreateFixturesTask.php';

    // launching the task
    analyzeFixtures(self::CONFIG_FOLDER_YML_FIXTURES . self::DATABASE_FIRST_TABLE_NAME . '.yml');
  }

  /**
   * @doesNotPerformAssertions
   * @throws OtraException
   * @throws ReflectionException
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
    $_SERVER['REMOTE_ADDR'] = '::1';
    Session::init();

    Database::init(self::DATABASE_CONNECTION);
    setScopeProtectedFields(
      Database::class,
      [
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    $confToUse = $database = null;

    // launching the task
    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Testing with $database = null
   *
   * @throws ReflectionException|OtraException
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

    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @doesNotPerformAssertions
   * @throws OtraException
   * @throws ReflectionException
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    require CONSOLE_PATH . 'database/sqlCreateDatabase/sqlCreateDatabaseTask.php';
    sqlCreateDatabase([self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME]);

    // launching the task
    $confToUse = self::DATABASE_CONNECTION;
    $database = self::DATABASE_NAME;
    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Test with a non-existent database.
   *
   * @throws ReflectionException|OtraException
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
    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }
}
