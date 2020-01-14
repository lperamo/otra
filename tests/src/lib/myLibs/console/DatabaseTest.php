<?php
use config\AllConfig;
use PHPUnit\Framework\TestCase;
use lib\myLibs\
{OtraException, console\Database, bdd\Sql, Session};

define('INIT_IMPORTS_FUNCTION', '_initImports');

/**
 * @runTestsInSeparateProcesses
 */
class DatabaseTest extends TestCase
{
  const TEST_CONFIG_PATH = 'tests/config/AllConfig.php';
  const TEST_CONFIG_GOOD_PATH = 'tests/config/AllConfigGood.php';
  protected $preserveGlobalState = FALSE; // to fix some bugs like 'constant VERBOSE already defined

  private static
    $configFolder = BASE_PATH . 'tests/src/bundles/HelloWorld/config/data/',
    $configBackupFolder = BASE_PATH . 'tests/config/data/',
    $databaseConnection = 'test',
    $databaseFirstTableName = 'testDB_table',
    $databaseName = 'testDB',
    $fixturesFile = 'db_fixture',
    $schemaFile = 'schema.yml',
    $schemaAbsolutePath,
    $importedSchemaAbsolutePath,
    $schemaFileBackup,
    $tablesOrderFile = 'tables_order.yml',
    $tablesOrderFilePath,
    $tablesOrder = ['testDB_table2', 'testDB_table3', 'testDB_table'],
    $configFolderSql,
    $configFolderSqlBackup,
    $configFolderSqlFixtures,
    $configFolderSqlFixturesBackup,
    $configFolderYml,
    $configFolderYmlBackup,
    $configFolderYmlFixtures,
    $configFolderYmlFixturesBackup;

  /**
   * @throws ReflectionException
   */
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    removeFieldScopeProtection(Database::class, 'boolSchema')->setValue(false);
    removeFieldScopeProtection(Database::class, 'folder')->setValue('tests/src/bundles/');
    self::$configFolderSql = self::$configFolder . 'sql/';
    self::$configFolderSqlBackup = self::$configBackupFolder . 'sqlBackup/';
    self::$configFolderSqlFixtures = self::$configFolderSql . 'fixtures/';
    self::$configFolderSqlFixturesBackup = self::$configFolderSqlBackup . 'fixtures/';
    self::$configFolderYml = self::$configFolder . 'yml/';
    self::$configFolderYmlFixtures = self::$configFolderYml . 'fixtures/';
    self::$configFolderYmlBackup = self::$configBackupFolder . 'ymlBackup/';
    self::$configFolderYmlFixturesBackup = self::$configFolderYmlBackup . 'fixtures/';

    self::$schemaFileBackup = self::$configFolderYmlBackup . self::$schemaFile;
    self::$schemaAbsolutePath = self::$configFolderYml . self::$schemaFile;
    self::$importedSchemaAbsolutePath = self::$configFolderYml . 'importedSchema.yml';
    self::$tablesOrderFilePath = self::$configFolderYml . self::$tablesOrderFile;
  }

  /**
   * @throws OtraException
   */
  protected function tearDown(): void
  {
    $this->cleanAll();
  }

  /**
   * Clean files and the database that are created for tests.
   *
   * @throws OtraException
   */
  protected function cleanAll() : void
  {
    $this->cleanFileAndFolders([
      self::$configFolderSql,
      self::$configFolderYml
    ]);

    require_once(BASE_PATH . self::TEST_CONFIG_GOOD_PATH);

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::$databaseName . '`;');
  }

  /**
   * Removes all files and folders specified in the array.
   *
   * @param array $fileOrFolders
   *
   * @throws OtraException If we cannot remove a file or a folder
   */
  private function cleanFileAndFolders(array $fileOrFolders) : void
  {
    foreach ($fileOrFolders as &$folder)
    {
      if (true === file_exists($folder))
      {
        $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file)
        {
          $realPath = $file->getRealPath();
          $method = true === $file->isDir() ? 'rmdir' : 'unlink';

          if (false === $method($realPath))
            throw new OtraException('Cannot remove the file/folder \'' . $realPath . '\'.', E_CORE_ERROR);
        }

        $exceptionMessage = 'Cannot remove the folder \'' . $folder . '\'.';

        try
        {
          if (false === rmdir($folder))
            throw new OtraException($exceptionMessage, E_CORE_ERROR);
        } catch (Exception $e)
        {
          throw new OtraException('Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ' . $exceptionMessage, $e->getCode());
        }
      }
    }
  }

  /**
   * Copy the file or an entire folder to the destination
   *
   * @param array $filesOrFoldersSrc Must be the absolute path
   * @param array $filesOrFoldersDest Must be the absolute path
   *
   * @throws OtraException If we can't create a folder or copy a file.
   */
  private function copyFileAndFolders(array $filesOrFoldersSrc, array $filesOrFoldersDest) : void
  {
    foreach ($filesOrFoldersSrc as $key => &$fileOrFolderSrc)
    {
      $fileOrFolderDest = $filesOrFoldersDest[$key];
      $isDirFileOrFolderSrc = is_dir($fileOrFolderSrc);
      $initialFolder = $isDirFileOrFolderSrc ? $fileOrFolderDest : dirname($fileOrFolderDest);

      if (false === file_exists($initialFolder) && false === mkdir($initialFolder, 0777, true))
        throw new OtraException('Cannot create the folder ' . $initialFolder);

      if (true === file_exists($fileOrFolderSrc))
      {
        // If it just a file, we don't have to iterate on it !
        if (true === $isDirFileOrFolderSrc)
        {
          $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fileOrFolderSrc, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
          );

          // We have to make a temporary array from the results of the iterator because it isn't sorted alphabetically
          // and then the folder names come after files ... or we have to create the folders before the files !
          $filesAndFoldersArray = [];

          foreach ($files as $file)
          {
            $filesAndFoldersArray[$file->getBaseName()] = $file->getRealPath();
          }

          unset($files, $file);

          sort($filesAndFoldersArray);
        } else
          $filesAndFoldersArray = [$fileOrFolderSrc];

        foreach ($filesAndFoldersArray as $basename => &$file)
        {
          $newPath = $fileOrFolderDest . str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($fileOrFolderSrc)));

          if (true === is_dir($file))
          {
            if (false === mkdir($newPath))
              throw new OtraException('Cannot create the folder \'' . $newPath . '\'.', E_CORE_ERROR);
          } else
          {
            if (false === copy($file, $newPath))
              throw new OtraException('Cannot copy the file \'' . $basename . ' to ' . $newPath . '\'.', E_CORE_ERROR);
          }
        }
      }
    }
  }

  /**
   * Loads a main configuration specific to test purposes.
   */
  private function loadConfig() : void
  {
    require(BASE_PATH . self::TEST_CONFIG_GOOD_PATH);

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
  }

  /**
   * @throws ReflectionException
   * @depends testGetDirs
   *
   * @author Lionel Péramo
   */
  public function testInitBase() : void
  {
    Database::initBase();

    // We test each private static variable that has been set by Database::initBase()
    $this->assertEquals(
      Database::getDirs(),
      removeFieldScopeProtection(Database::class, 'baseDirs')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'baseDirs')->getValue()[0] . 'config/data/yml/',
      removeFieldScopeProtection(Database::class, 'pathYml')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'pathYml')->getValue() . 'fixtures/',
      removeFieldScopeProtection(Database::class, 'pathYmlFixtures')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'baseDirs')->getValue()[0] . 'config/data/sql/',
      removeFieldScopeProtection(Database::class, 'pathSql')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'pathSql')->getValue() . 'fixtures/',
      removeFieldScopeProtection(Database::class, 'pathSqlFixtures')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'pathYml')->getValue() . self::$schemaFile,
      removeFieldScopeProtection(Database::class, 'schemaFile')->getValue()
    );

    $this->assertEquals(
      removeFieldScopeProtection(Database::class, 'pathYml')->getValue() . self::$tablesOrderFile,
      removeFieldScopeProtection(Database::class, 'tablesOrderFile')->getValue()
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
    Database::init(self::$databaseConnection);
  }

  /**
   * @author Lionel Péramo
   */
  public function testGetDirs() : void
  {
    require(BASE_PATH . self::TEST_CONFIG_GOOD_PATH);
    $dirs = Database::getDirs();
    $this->assertIsArray($dirs);
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
    $this->copyFileAndFolders(
      [
        self::$configFolderYmlBackup,
        self::$configFolderSqlBackup
      ],
      [
        self::$configFolderYml,
        self::$configFolderSql
      ]
    );

    Database::clean();
    $sqlPath = removeFieldScopeProtection(Database::class, 'pathSql')->getValue();
    $this->assertEquals([], glob($sqlPath . '/*.sql'));
    $this->assertEquals([], glob($sqlPath . 'truncate/*.sql'));
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
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    // Launching the task
    Database::createDatabase(self::$databaseName);

    // Assertions
    $endPath = removeFieldScopeProtection(Database::class, '_databaseFile')->getValue() . '.sql';
    $this->assertFileEquals(self::$configFolderSqlBackup . $endPath, self::$configFolderSql . $endPath);
  }


  /**
   * @author Lionel Péramo
   * TODO Do a complete test, not just on the type
   */
  public function testGetAttr() : void
  {
    $attrTest = Database::getAttr('test');
    $this->assertIsString($attrTest);
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
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    // loading test configuration
    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    Database::createDatabase(self::$databaseName);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'pathSqlFixtures' => self::$configFolderSqlFixtures,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    $sortedTables = [];
    Database::createFixture(
      self::$databaseName,
      self::$databaseFirstTableName,
      [],
      [],
      [],
      $sortedTables,
      self::$configFolderSqlFixtures . self::$databaseName . '_' . self::$databaseFirstTableName . '.sql'
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly_NoSchema() : void
  {
    // Creating the context
    $this->copyFileAndFolders(
      [self::$configFolderYmlFixturesBackup],
      [self::$configFolderYmlFixtures]
    );

    // loading test configuration
    $this->loadConfig();

    // Launching the task
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('You have to create a database schema file in config/data/' . self::$schemaFile . ' before using fixtures. Searching for : ');
    Database::createFixtures(self::$databaseName, 1);
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
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlBackup . self::$tablesOrderFile,
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$tablesOrderFilePath,
        self::$configFolderYmlFixtures
      ]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    try
    {
      Database::createDatabase(self::$databaseName);
    } catch (OtraException $le)
    {
      echo 'Schema already exists', PHP_EOL;
    }

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
        'pathSqlFixtures' => self::$configFolderSqlFixtures,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    // launching task
    Database::createFixtures(self::$databaseName, 1);
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
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$configFolderYmlFixtures
      ]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    // assertions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('You must use the database generation task before using the fixtures (no ' .
      substr(self::$tablesOrderFilePath, strlen(BASE_PATH)) . ' file)');

    // launching the task
    Database::createFixtures(self::$databaseName, 1);
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
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlBackup . self::$tablesOrderFile,
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$tablesOrderFilePath,
        self::$configFolderYmlFixtures
      ]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    Database::createDatabase(self::$databaseName);

    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
        'pathSqlFixtures' => self::$configFolderSqlFixtures,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    // testing
    Database::createFixtures(self::$databaseName, 2);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testExecuteFile_DoesNotExist() : void
  {
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);

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
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    removeFieldScopeProtection(Database::class, '_databaseFile');

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    // Launching the tasks
    Database::createDatabase(self::$databaseName);
    Database::truncateTable(self::$databaseName, self::$databaseFirstTableName);
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
    echo _DIR_;
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
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderSqlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$configFolderSqlFixtures
      ]
    );

    $this->loadConfig();

    // context - We create the database
    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
      ]
    );
    Database::createDatabase(self::$databaseName);

    removeFieldScopeProtection(Database::class, 'pathSqlFixtures')->setValue(self::$configFolderSqlFixtures);

    // launching task
//    Database::createFixture(
//      self::$databaseName,
//      self::$databaseFirstTableName,
//      $fixturesData[self::$tablesOrder[0]],
//      $schema[self::$databaseFirstTableName],
//      self::$tablesOrder,
//      $fixturesMemory,
//      self::$configFolderSql . self::$fixturesFile . '/' . self::$databaseName . '_' . self::$databaseFirstTableName . '.sql'
//    );

//    removeMethodScopeProtection(Database::class, '_executeFixture')
//      ->invokeArgs(null, [self::$databaseName, self::$tablesOrder[0]]);
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
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    define('VERBOSE', 2);

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    Database::createDatabase(self::$databaseName);

    // launching the task
    $sqlInstance = Database::dropDatabase(self::$databaseName);
    $this->assertInstanceOf(Sql::class, $sqlInstance);
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
        'schemaFile' => self::$schemaAbsolutePath,
        'pathSql' => self::$configFolderSql,
      ]
    );

    // launching the task
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage("The file '" . substr(self::$schemaAbsolutePath, strlen(BASE_PATH)) . "' does not exist. We can't generate the SQL schema without it.");
    Database::generateSqlSchema(self::$databaseName);
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
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    Database::init(self::$databaseConnection);

    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::$databaseName);
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
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    Database::init(self::$databaseConnection);

    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
      ]
    );

    // launching the task
    Database::generateSqlSchema(self::$databaseName, true);
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
    $this->copyFileAndFolders([self::$configFolderYmlFixturesBackup], [self::$configFolderYmlFixtures]);

    // launching the task
    removeMethodScopeProtection(Database::class, '_analyzeFixtures')
      ->invokeArgs(null, [self::$configFolderYmlFixtures . self::$databaseFirstTableName . '.yml']);
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
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
      ]
    );

    Database::createDatabase(self::$databaseName);

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
    $confToUse = self::$databaseConnection;
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
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::$databaseConnection);
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    Database::createDatabase(self::$databaseName);

    // launching the task
    $confToUse = self::$databaseConnection;
    $database = self::$databaseName;
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
    $confToUse = self::$databaseConnection;
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
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::$databaseConnection);

    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql
      ]
    );

    Database::createDatabase(self::$databaseName);

    // we change the path to the schema.yml in order to not overwrite the existing one by precaution
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$importedSchemaAbsolutePath);

    // launching task
    Database::importSchema(self::$databaseName, self::$databaseConnection);
    $this->assertFileExists(self::$importedSchemaAbsolutePath);
    $this->assertFileEquals(self::$schemaFileBackup, self::$importedSchemaAbsolutePath);
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
    $this->copyFileAndFolders(
      [
        self::$configFolderYmlBackup
      ],
      [
        self::$configFolderYml
      ]
    );

    $this->loadConfig();

    // Initialize OTRA session
    Session::init();

    Database::init(self::$databaseConnection);

    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    Database::createDatabase(self::$databaseName);

    // restores correct content in the variable overwritten by the function call
    setScopeProtectedFields(
      Database::class,
      [
        'schemaFile' => self::$schemaAbsolutePath,
        'tablesOrderFile' => self::$tablesOrderFilePath,
        'pathSql' => self::$configFolderSql,
        'pathSqlFixtures' => self::$configFolderSqlFixtures,
        'pathYmlFixtures' => self::$configFolderYmlFixtures
      ]
    );

    Database::createFixtures(self::$databaseName, 1);

    // restores correct content in the variable overwritten by the function call
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFilePath);

    // launching the task
    Database::importFixtures(self::$databaseName, self::$databaseConnection);

    foreach (self::$tablesOrder as &$table)
    {
      $ymlFile = self::$configFolderYmlFixtures . $table . '.yml';
      $this->assertFileExists(self::$configFolderYmlFixtures . $table . '.yml');
      $this->assertFileEquals(self::$configFolderYmlFixturesBackup . $table . '.yml', $ymlFile);
    }
  }
}
