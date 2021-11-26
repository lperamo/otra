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
use const otra\cache\php\{APP_ENV,BASE_PATH,CORE_PATH,PROD,TEST_PATH};
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};
use function otra\tools\
{cleanFileAndFolders,
  copyFileAndFolders,
  removeFieldsScopeProtection,
  setScopeProtectedFields};

/**
 * @runTestsInSeparateProcesses
 *
 * @author Lionel Péramo
 */
class DatabaseTest extends TestCase
{
  private const
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_NAME = 'testDB',
    TRUNCATE_ONLY = 1,
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
    CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES = self::CONFIG_FOLDER_SQL . 'truncate/',
    CONFIG_FOLDER_SQL_TRUNCATE_FIXTURES_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . 'truncate/',
    SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH = self::CONFIG_FOLDER_SQL . self::SQL_SCHEMA_FORCE_FILE,
    SQL_SCHEMA_FORCE_FILE_ABSOLUTE_PATH_BACKUP = self::CONFIG_FOLDER_SQL_BACKUP . self::SQL_SCHEMA_FORCE_FILE,
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    CONFIG_FOLDER_YML_FIXTURES = self::CONFIG_FOLDER_YML . self::OTRA_LABEL_FIXTURES_FOLDER,
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/',
    CONFIG_FOLDER_YML_FIXTURES_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::OTRA_LABEL_FIXTURES_FOLDER,
    SCHEMA_FILE_BACKUP = self::CONFIG_FOLDER_YML_BACKUP . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    IMPORTED_SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . 'importedSchema.yml',
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . self::TABLES_ORDER_FILE,
    TABLES_ORDER = ['testDB_table2', 'testDB_table3', 'testDB_table'],
    INIT_IMPORTS_FUNCTION = '_initImports',
    OTRA_LABEL_FIXTURES_FOLDER = 'fixtures/',
    OTRA_VARIABLE_DATABASE_BASE_DIRS = 'baseDirs',
    OTRA_VARIABLE_DATABASE_PATH_SQL = 'pathSql',
    OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES = 'pathSqlFixtures',
    OTRA_VARIABLE_DATABASE_PATH_YML = 'pathYml',
    OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES = 'pathYmlFixtures',
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE = 'tablesOrderFile';

  protected $preserveGlobalState = FALSE; // to fix some bugs like 'constant VERBOSE already defined

  /**
   * @throws ReflectionException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
    $reflectedClass = (new ReflectionClass(Database::class));
    $reflectedClass->getProperty('boolSchema')->setValue(false);
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
   */
  public function testInitBase() : void
  {
    // launching
    Database::initBase();

    // testing
    $reflectedClass = (new ReflectionClass(Database::class));
    $baseDirs = $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_BASE_DIRS)->getValue();
    $ymlPath = $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_YML)->getValue();
    $sqlPath = $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue();

    // We test each private static variable that has been set by Database::initBase()
    self::assertEquals(
      $ymlPath . self::OTRA_LABEL_FIXTURES_FOLDER,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES)->getValue()
    );

    self::assertEquals(
      $sqlPath . self::OTRA_LABEL_FIXTURES_FOLDER,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES)->getValue()
    );

    self::assertEquals(
      $ymlPath . self::SCHEMA_FILE,
      $reflectedClass->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)->getValue()
    );

    self::assertEquals(
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
      self::DATABASE_NAME,
      'InnoDB',
      '3c>*v(U;Rhoq77[}',
      'root',
      self::CONFIG_FOLDER_YML_FIXTURES,
      true
    ];

    foreach ($unprotectedFields as $fieldNameKey => $unprotectedField)
    {
      if ($testKeys[$fieldNameKey] === 'pathYmlFixtures')
        continue;

      self::assertEquals(
        $testValues[$fieldNameKey],
        $unprotectedField->getValue(),
        'Testing the field ' . CLI_INFO_HIGHLIGHT . $testKeys[$fieldNameKey] . END_COLOR
      );
    }
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
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
    $sqlPath = (new ReflectionClass(Database::class))
      ->getProperty(self::OTRA_VARIABLE_DATABASE_PATH_SQL)->getValue();
    self::assertEquals([], glob($sqlPath . '/*.sql'));
    self::assertEquals([], glob($sqlPath . 'truncate/*.sql'));
  }

  /**
   * @throws OtraException If the original YAML schema can't be copied.
   * @throws ReflectionException
   * depends testInit
   * depends testDropDatabase
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    // Launching the task
    Database::createDatabase(self::DATABASE_NAME);

    // Assertions
    $endPath = (new ReflectionClass(Database::class))->getProperty('databaseFile')->getValue() . '.sql';
    self::assertFileEquals(
      self::CONFIG_FOLDER_SQL_BACKUP . $endPath,
      self::CONFIG_FOLDER_SQL . $endPath,
      'Comparing ' . CLI_INFO_HIGHLIGHT . self::CONFIG_FOLDER_SQL_BACKUP . $endPath . CLI_ERROR . ' against ' .
      CLI_INFO_HIGHLIGHT . self::CONFIG_FOLDER_SQL . $endPath . CLI_ERROR
    );
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
    $this->expectExceptionMessage(
      'You have to create a database schema file in ' . CLI_INFO_HIGHLIGHT . 'config/data/' . self::SCHEMA_FILE .
      CLI_BASE . ' before using fixtures. Searching for : '
    );
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
   */
  public function testCreateFixtures_TruncateOnly() : void
  {
    // context
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

    $this->loadConfig();

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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL_FIXTURES => self::CONFIG_FOLDER_SQL_FIXTURES,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );
    ob_end_clean();

    // launching task
    ob_start();
    Database::createFixtures(self::DATABASE_NAME, self::TRUNCATE_ONLY);

    // testing
    self::assertEquals(
      PHP_EOL . CLI_INFO_HIGHLIGHT . CLI_INFO_HIGHLIGHT . 'testDB.testDB_table2' . END_COLOR . PHP_EOL .
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

  /**
   * @throws OtraException
   * @throws ReflectionException
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

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

    // testing
    Database::createFixtures(self::DATABASE_NAME, 2);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testExecuteFile_DoesNotExist() : void
  {
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)
      ->setValue(self::SCHEMA_ABSOLUTE_PATH);

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
    Database::createDatabase(self::DATABASE_NAME);
    Database::truncateTable(self::DATABASE_NAME, self::DATABASE_FIRST_TABLE_NAME);
  }

  /**
   * @doesNotPerformAssertions
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
    Database::createDatabase(self::DATABASE_NAME);

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

    // launching the task
    (new ReflectionMethod(Database::class, '_analyzeFixtures'))
      ->invokeArgs(null, [self::CONFIG_FOLDER_YML_FIXTURES . self::DATABASE_FIRST_TABLE_NAME . '.yml']);
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

    Database::createDatabase(self::DATABASE_NAME);

    $confToUse = $database = null;

    // launching the task
    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Testing with $database = null
   *
   * @throws ReflectionException
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

    Database::createDatabase(self::DATABASE_NAME);

    // launching the task
    $confToUse = self::DATABASE_CONNECTION;
    $database = self::DATABASE_NAME;
    (new ReflectionMethod(Database::class, self::INIT_IMPORTS_FUNCTION))
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * Test with a non-existent database.
   *
   * @throws ReflectionException
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

  /**
   * @throws OtraException
   * @throws ReflectionException
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

    // we change the path to the schema.yml in order to not overwrite the existing one by precaution
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)
      ->setValue(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);

    // launching task
    Database::importSchema(self::DATABASE_NAME, self::DATABASE_CONNECTION);
    self::assertFileExists(self::IMPORTED_SCHEMA_ABSOLUTE_PATH);
    self::assertFileEquals(
      self::SCHEMA_FILE_BACKUP,
      self::IMPORTED_SCHEMA_ABSOLUTE_PATH,
      'Comparing ' . CLI_INFO_HIGHLIGHT . self::IMPORTED_SCHEMA_ABSOLUTE_PATH . CLI_ERROR . ' against ' .
      CLI_INFO_HIGHLIGHT . self::SCHEMA_FILE_BACKUP . CLI_ERROR
    );
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @depends testInit
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
        self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE => self::SCHEMA_ABSOLUTE_PATH,
        self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE => self::TABLES_ORDER_FILE_PATH,
        self::OTRA_VARIABLE_DATABASE_PATH_SQL => self::CONFIG_FOLDER_SQL,
        self::OTRA_VARIABLE_DATABASE_PATH_YML_FIXTURES => self::CONFIG_FOLDER_YML_FIXTURES
      ]
    );

    Database::createDatabase(self::DATABASE_NAME);

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

    Database::createFixtures(self::DATABASE_NAME, 1);

    // restores correct content in the variable overwritten by the function call
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_TABLES_ORDER_FILE)
      ->setValue(self::TABLES_ORDER_FILE_PATH);

    // launching the task
    Database::importFixtures(self::DATABASE_NAME, self::DATABASE_CONNECTION);

    foreach (self::TABLES_ORDER as $table)
    {
      $ymlFile = self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml';
      self::assertFileExists(self::CONFIG_FOLDER_YML_FIXTURES . $table . '.yml');
      self::assertFileEquals(self::CONFIG_FOLDER_YML_FIXTURES_BACKUP . $table . '.yml', $ymlFile);
    }
  }
}
