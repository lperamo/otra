<?
use config\AllConfig;
use PHPUnit\Framework\TestCase;
use lib\myLibs\
{LionelException, console\Database, bdd\Sql};

define('INIT_IMPORTS_FUNCTION', '_initImports');

/**
 * @runTestsInSeparateProcesses
 */
class DatabaseTest extends TestCase
{
  protected $preserveGlobalState = FALSE; // to fix some bugs like 'constant VERBOSE already defined

  private static
    $configFolder = BASE_PATH . 'tests/config/data/',
    $databaseConnection = 'test',
    $databaseFirstTableName = 'testDB_table',
    $databaseName = 'testDB',
    $fixturesFile = 'db_fixture',
    $schemaFile = 'schema.yml',
    $schemaAbsolutePath,
    $importedSchemaAbsolutePath,
    $schemaFileBackup,
    $tablesOrderFile = 'tables_order',
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
    self::$configFolderSqlBackup = self::$configFolder . 'sqlBackup/';
    self::$configFolderSqlFixtures = self::$configFolderSql . 'fixtures/';
    self::$configFolderSqlFixturesBackup = self::$configFolderSqlBackup . 'fixtures/';
    self::$configFolderYml = self::$configFolder . 'yml/';
    self::$configFolderYmlFixtures = self::$configFolderYml . 'fixtures/';
    self::$configFolderYmlBackup = self::$configFolder . 'ymlBackup/';
    self::$configFolderYmlFixturesBackup = self::$configFolderYmlBackup . 'fixtures/';

    self::$schemaFileBackup = self::$configFolderYmlBackup . self::$schemaFile;
    self::$schemaAbsolutePath = self::$configFolderYml . self::$schemaFile;
    self::$importedSchemaAbsolutePath = self::$configFolderYml . 'importedSchema.yml';
  }

  /**
   * @throws LionelException
   */
  protected function tearDown(): void
  {
    $this->cleanAll();
  }

  /**
   * Clean files and the database that are created for tests.
   *
   * @throws LionelException
   */
  protected function cleanAll()
  {
    $this->cleanFileAndFolders([
      self::$configFolderSql,
      self::$configFolderYml
    ]);

    require_once(BASE_PATH . 'tests/config/AllConfig.php');

    Sql::getDb(false, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::$databaseName . '`;');
  }

  /**
   * Removes all files and folders specified in the array.
   *
   * @param array $fileOrFolders
   *
   * @throws LionelException If we cannot remove a file or a folder
   */
  private function cleanFileAndFolders(array $fileOrFolders)
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
            throw new LionelException('Cannot remove the file/folder \'' . $realPath . '\'.', E_CORE_ERROR);
        }

        $exceptionMessage = 'Cannot remove the folder \'' . $folder . '\'.';

        try
        {
          if (false === rmdir($folder))
            throw new LionelException($exceptionMessage, E_CORE_ERROR);
        } catch (Exception $e)
        {
          throw new LionelException('Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ' . $exceptionMessage, $e->getCode());
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
   * @throws LionelException If we can't create a folder or copy a file.
   */
  private function copyFileAndFolders(array $filesOrFoldersSrc, array $filesOrFoldersDest)
  {
    foreach ($filesOrFoldersSrc as $key => &$fileOrFolderSrc)
    {
      $fileOrFolderDest = $filesOrFoldersDest[$key];
      $isDirFileOrFolderSrc = is_dir($fileOrFolderSrc);
      $initialFolder = $isDirFileOrFolderSrc ? $fileOrFolderDest : dirname($fileOrFolderDest);

      if (false === file_exists($initialFolder) && false === mkdir($initialFolder, 0777, true))
        throw new LionelException('Cannot create the folder ' . $initialFolder);

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
              throw new LionelException('Cannot create the folder \'' . $newPath . '\'.', E_CORE_ERROR);
          } else
          {
            if (false === copy($file, $newPath))
              throw new LionelException('Cannot copy the file \'' . $basename . ' to ' . $newPath . '\'.', E_CORE_ERROR);
          }
        }
      }
    }
  }

  /**
   * Loads a main configuration specific to test purposes.
   */
  private function loadConfig()
  {
    require(BASE_PATH . 'tests/config/AllConfig.php');

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
  }

  /**
   * @throws ReflectionException
   * depends on testGetDirs
   *
   * @author Lionel Péramo
   */
  public function testInitBase()
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
      removeFieldScopeProtection(Database::class, 'pathYml')->getValue() . self::$tablesOrderFile . '.yml',
      removeFieldScopeProtection(Database::class, 'tablesOrderFile')->getValue()
    );
  }

  /**
   * @throws LionelException
   *
   * TODO Put assertions and remove the related annotation!
   * depends on testInitBase
   * @doesNotPerformAssertions
   * @author                         Lionel Péramo
   */
  public function testInit()
  {
    $this->loadConfig();
    Database::init(self::$databaseConnection);
  }

  /**
   * @author Lionel Péramo
   */
  public function testGetDirs()
  {
    $dirs = Database::getDirs();
    $this->assertIsArray($dirs);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * TODO add files before the test to test if they are cleaned
   *
   * @author Lionel Péramo
   */
  public function testClean()
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
   * @throws LionelException If the original YAML schema can't be copied.
   * @throws ReflectionException
   * depends on testInit, testInitCommand, testDropDatabase
   * @author Lionel Péramo
   */
  public function testCreateDatabase()
  {
    // Creating the context
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

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
  public function testGetAttr()
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
  public function test_SortTableByForeignKeysEmpty()
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
  public function test_SortTableByForeignKeys()
  {
//    $_sortTableByForeignKeys = new ReflectionMethod(Database::class, '_sortTableByForeignKeys');
//    $_sortTableByForeignKeys->setAccessible(true);
//    $sortedTables = [];
//    $_sortTableByForeignKeys->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixture()
  {
    // Creating the context
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaFile]
    );

    // loading test configuration
    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaFile);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

    Database::createDatabase(self::$databaseName);

    // restores correct content in the variable overwritten by the function call
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);

    $sortedTables = [];
    Database::createFixture(self::$databaseName, self::$databaseFirstTableName, [], [], [], $sortedTables, 'testFile');
  }

  /**
   * @throws LionelException
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly_NoSchema()
  {
    // Creating the context
    $this->copyFileAndFolders(
      [self::$configFolderYmlFixturesBackup],
      [self::$configFolderYmlFixtures]
    );

    // loading test configuration
    $this->loadConfig();

    // Launching the task
    $this->expectException(LionelException::class);
    $this->expectExceptionMessage('You have to create a database schema file in config/data/' . self::$schemaFile . ' before using fixtures. Searching for : ');
    Database::createFixtures(self::$databaseName, 1);
  }


  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * depends on testInit, testInitCommand, testCreateDatabase, testTruncateTable, testCreateFixture, test_ExecuteFixture
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly()
  {
    // context
    define('VERBOSE', 2);
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlBackup . self::$tablesOrderFile . '.yml',
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$configFolderYml . self::$tablesOrderFile . '.yml',
        self::$configFolderYmlFixtures
      ]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

    try
    {
      Database::createDatabase(self::$databaseName);
    } catch (LionelException $le)
    {
      echo 'Schema already exists', PHP_EOL;
    }

    removeFieldScopeProtection(Database::class, 'pathYmlFixtures')->setValue(self::$configFolderYmlFixtures);

    // launching task
    Database::createFixtures(self::$databaseName, 1);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly_NoTablesOrderFile()
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

    $tablesOrderFileAbsolutePath = self::$configFolderYml . self::$tablesOrderFile . '.yml';
    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue($tablesOrderFileAbsolutePath);

    // assertions
    $this->expectException(LionelException::class);
    $this->expectExceptionMessage('You must use the database generation task before using the fixtures (no ' .
      substr($tablesOrderFileAbsolutePath, strlen(BASE_PATH)) . ' file)');

    // launching the task
    Database::createFixtures(self::$databaseName, 1);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testCreateFixtures_CleanAndTruncate()
  {
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlBackup . self::$tablesOrderFile,
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaAbsolutePath,
        self::$configFolderYml . self::$tablesOrderFile,
        self::$configFolderYmlFixtures
      ]
    );

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

    Database::createDatabase(self::$databaseName);

    removeFieldScopeProtection(Database::class, 'pathYmlFixtures')->setValue(self::$configFolderYmlFixtures);

    Database::createFixtures(self::$databaseName, 2);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testExecuteFile_DoesNotExist()
  {
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);

    $this->expectException(LionelException::class);
    $this->expectExceptionMessage('The file "blabla" doesn\'t exist !');
    Database::executeFile('blabla');
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   * depends on testInitBase, testCreateDatabase
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testTruncateTable()
  {
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaAbsolutePath]
    );

    removeFieldScopeProtection(Database::class, '_databaseFile');

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

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
  public function testExecuteFile_Exists()
  {
    echo _DIR_;
    //    Database::executeFile();
  }


  /**
   * @throws LionelException
   * @throws ReflectionException
   * depends on testInit, testInitCommand
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testExecuteFixture()
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
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    Database::createDatabase(self::$databaseName);

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

    removeMethodScopeProtection(Database::class, '_executeFixture')
      ->invokeArgs(null, [self::$databaseName, self::$tablesOrder[0]]);
  }

  /**
   * @throws LionelException
   *
   * TODO Do a complete test not just a type assertion
   * @author Lionel Péramo
   */
  public function testDropDatabase()
  {
    define('VERBOSE', 2);

    $this->loadConfig();

    Database::createDatabase(self::$databaseName);

    $sqlInstance = Database::dropDatabase(self::$databaseName);
    $this->assertInstanceOf(Sql::class, $sqlInstance);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * depends on testInitBase
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_NoSchema()
  {
    // Creating the context
    Database::initBase();
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

    // launching the task
    $this->expectException(LionelException::class);
    $this->expectExceptionMessage("The file '" . substr(self::$schemaAbsolutePath, strlen(BASE_PATH)) . "' doesn't exist. We can't generate the SQL schema without it.");
    Database::generateSqlSchema(self::$databaseName);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * depends on testInitBase
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_DontForce()
  {
    // Creating the context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);
    Database::initBase();

    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);

    // launching the task
    Database::generateSqlSchema(self::$databaseName);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * depends on testInitBase
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testGenerateSqlSchema_Force()
  {
    // Creating the context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);
    Database::initBase();

    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);

    // launching the task
    Database::generateSqlSchema(self::$databaseName, true);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * TODO Create a test fixture file in order to test that function !
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testAnalyzeFixtures()
  {
    // context
    $this->copyFileAndFolders([self::$configFolderYmlFixturesBackup], [self::$configFolderYmlFixtures]);

    // launching the task
    removeMethodScopeProtection(Database::class, '_analyzeFixtures')
      ->invokeArgs(null, [self::$configFolderYmlFixtures . self::$databaseFirstTableName . '.yml']);
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
  public function testInitImports_AllNull()
  {
    $this->loadConfig();

    Database::createDatabase(self::$databaseName);

    $confToUse = $database = null;
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
  public function testInitImports_DatabaseNull()
  {
    // context
    $confToUse = self::$databaseConnection;
    $database = null;

    $this->loadConfig();

    $this->expectException(LionelException::class);
    $this->expectExceptionMessage("The database 'testDB' doesn't exist.");

    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotations
   * @author Lionel Péramo
   */
  public function testInitImports_NoNull()
  {
    // context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    Database::init(self::$databaseConnection);
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
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
  public function testInitImports_BadDatabase()
  {
    // context
    $confToUse = self::$databaseConnection;
    $database = 'noBDD';

    $this->loadConfig();

    // assertions about exceptions
    $this->expectException(LionelException::class);
    $this->expectExceptionMessage("The database 'noBDD' doesn't exist.");

    // launching task
    removeMethodScopeProtection(Database::class, INIT_IMPORTS_FUNCTION)
      ->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testImportSchema()
  {
    // context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaAbsolutePath]);

    $this->loadConfig();

    Database::initBase();

    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$tablesOrderFile);
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);

    Database::createDatabase(self::$databaseName);

    // we change the path to the schema.yml in order to not overwrite the existing one by precaution
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$importedSchemaAbsolutePath);

    // launching task
    Database::importSchema(self::$databaseName, self::$databaseConnection);
    $this->assertFileExists(self::$importedSchemaAbsolutePath);
    $this->assertFileEquals(self::$schemaFileBackup, self::$importedSchemaAbsolutePath);
  }

  /**
   * @throws LionelException
   * @throws ReflectionException
   *
   * depends on testInit, testInitImports
   *
   * @author Lionel Péramo
   */
  public function testImportFixtures()
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

    Database::init(self::$databaseConnection);

    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$configFolderYml . self::$tablesOrderFile . '.yml');
    removeFieldScopeProtection(Database::class, 'pathSql')->setValue(self::$configFolderSql);
    removeFieldScopeProtection(Database::class, 'pathYmlFixtures')->setValue(self::$configFolderYmlFixtures);

    Database::createDatabase(self::$databaseName);

    // restores correct content in the variable overwritten by the function call
    removeFieldScopeProtection(Database::class, 'schemaFile')->setValue(self::$schemaAbsolutePath);

    Database::createFixtures(self::$databaseName, 1);

    // restores correct content in the variable overwritten by the function call
    removeFieldScopeProtection(Database::class, 'tablesOrderFile')->setValue(self::$configFolderYml . self::$tablesOrderFile . '.yml');

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

