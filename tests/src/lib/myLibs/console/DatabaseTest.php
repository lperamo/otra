<?
use phpunit\framework\TestCase;
use lib\myLibs\{LionelException, console\Database, bdd\Sql};
use config\All_Config;
use lib\sf2_yaml\Yaml;

/**
 * @runTestsInSeparateProcesses
 */
class DatabaseTest extends TestCase
{
  protected $preserveGlobalState = FALSE; // to fix some bugs like 'constant VERBOSE already defined

  private static
    $configFolder = BASE_PATH . 'tests/bundles/core/config/data/',
    $databaseConnection = 'test',
    $databaseFirstTableName = 'testDB_table',
    $databaseName = 'testDB',
    $fixturesFile = 'db_fixture',
    $schemaFile = 'Schema.yml',
    $schemaFileBackup,
    $tablesOrderFile = 'tables_order',
    $tablesOrder = ['testDB_table2','testDB_table3', 'testDB_table'],
    $configFolderSql,
    $configFolderSqlBackup,
    $configFolderSqlFixtures,
    $configFolderSqlFixturesBackup,
    $configFolderYml,
    $configFolderYmlBackup,
    $configFolderYmlFixtures,
    $configFolderYmlFixturesBackup;

  protected function setUp()
  {
    define('XMODE', 'PROD');
    Database::$boolSchema = false;
    Database::$folder = 'tests/bundles/';
    self::$configFolderSql = self::$configFolder . 'sql/';
    self::$configFolderSqlBackup = self::$configFolder . 'sqlBackup/';
    self::$configFolderSqlFixtures = self::$configFolderSql . 'fixtures/';
    self::$configFolderSqlFixturesBackup = self::$configFolderSqlBackup . 'fixtures/';
    self::$configFolderYml = self::$configFolder . 'yml/';
    self::$configFolderYmlFixtures = self::$configFolderYml . 'fixtures/';
    self::$configFolderYmlBackup = self::$configFolder . 'ymlBackup/';
    self::$configFolderYmlFixturesBackup = self::$configFolderYmlBackup . 'fixtures/';

    self::$schemaFileBackup = self::$configFolderYmlBackup . self::$schemaFile;
    self::$schemaFile = self::$configFolderYml . self::$schemaFile;
  }

  protected function tearDown()
  {
    $this->cleanAll();
  }

  protected function cleanAll()
  {
    $this->cleanFileAndFolders([
      self::$configFolderSql,
      self::$configFolderYml
    ]);
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
        }catch(Exception $e)
        {
          throw new LionelException('Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ' . $exceptionMessage, $e->getCode());
        }
      }
    }
  }

  /**
   * Copy the file or an entire folder to the destination
   *
   * @param array $filesOrFoldersSrc  Must be the absolute path
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

      if (false === file_exists($initialFolder) && false === mkdir($initialFolder, 0007, true))
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

          foreach($files as $file)
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
   * @author Lionel Péramo
   * depends on testGetDirs
   */
  public function testInitBase() { Database::initBase(); }

  /**
   * @author                         Lionel Péramo
   *
   * depends on testInitBase
   */
  public function testInit() { Database::init(self::$databaseConnection); }

  ///**
  // * @author Lionel Péramo
  // */
  //public function testInitCommand()
  //{
  //  define('VERBOSE', 2);
  //  Database::initCommand();
  //}

  /**
   * @author Lionel Péramo
   */
  public function testGetDirs() { Database::getDirs(); }

  /**
   * @author Lionel Péramo
   *         TODO add files before the test to test if they are cleaned
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
  }

  /**
   * @author Lionel Péramo
   * @throws LionelException If the original YAML schema can't be copied.
   * depends on testInit, testInitCommand, testDropDatabase
   */
  public function testCreateDatabase()
  {
    // Creating the context
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaFile]
    );

    $databaseClass = new ReflectionClass(Database::class);
    $_databaseFile = $databaseClass->getProperty('_databaseFile');
    $_databaseFile->setAccessible(true);

    // Launching the task
    Database::createDatabase(self::$databaseName);

    // Assertions
    $endPath = $_databaseFile->getValue() . '.sql';
    $this->assertFileEquals(self::$configFolderSqlBackup . $endPath, self::$configFolderSql . $endPath);
  }


  /**
   * @author Lionel Péramo
   */
  public function testGetAttr() { Database::getAttr('test'); }

  /**
   * @author Lionel Péramo
   */
  public function test_SortTableByForeignKeysEmpty()
  {
    $_sortTableByForeignKeys = new ReflectionMethod(Database::class, '_sortTableByForeignKeys');
    $_sortTableByForeignKeys->setAccessible(true);
    $sortedTables = [];
    $_sortTableByForeignKeys->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @author Lionel Péramo
   */
  public function test_SortTableByForeignKeys()
  {
//    $_sortTableByForeignKeys = new ReflectionMethod(Database::class, '_sortTableByForeignKeys');
//    $_sortTableByForeignKeys->setAccessible(true);
//    $sortedTables = [];
//    $_sortTableByForeignKeys->invokeArgs(null, [[], &$sortedTables]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateFixture()
  {
    $sortedTables = [];
    Database::createFixture(self::$databaseName, self::$databaseFirstTableName, [], [], [], $sortedTables, 'testFile');
  }

  /**
   * @author                   Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage You have to create a database schema file in config/data/schema.yml before using fixtures.
   */
  public function testCreateFixtures_TruncateOnly_NoSchema()
  {
    $this->copyFileAndFolders(
      [self::$configFolderYmlFixturesBackup],
      [self::$configFolderYmlFixtures]
    );
    Database::createFixtures(self::$databaseName, 1);
  }


  /**
   * @author Lionel Péramo
   * depends on testInit, testInitCommand, testCreateDatabase, testTruncateTable, testCreateFixture, test_ExecuteFixture
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
        self::$schemaFile,
        self::$configFolderYml . self::$tablesOrderFile . '.yml',
        self::$configFolderYmlFixtures
      ]
    );

    try
    {
      Database::createDatabase(self::$databaseName);
    } catch(LionelException $le)
    {
      echo 'Schema already exists', PHP_EOL;
    }

    // launching task
    Database::createFixtures(self::$databaseName, 1);
  }

  /**
   * @author                   Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage You must use the database generation task before using the fixtures (no tests/bundles/core/config/data/yml/tables_order.yml file)
   */
  public function testCreateFixtures_TruncateOnly_NoTablesOrderFile()
  {
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderYmlFixturesBackup
      ],
      [
        self::$schemaFile,
        self::$configFolderYmlFixtures
      ]
    );
    Database::createFixtures(self::$databaseName, 1);
  }

  /**
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
        self::$schemaFile,
        self::$configFolderYml . self::$tablesOrderFile,
        self::$configFolderYmlFixtures
      ]
    );
    Database::createDatabase(self::$databaseName);
    Database::createFixtures(self::$databaseName, 2);
  }

  /**
   * @author Lionel Péramo
   * depends on testInitBase, testCreateDatabase
   */
  public function testTruncateTable()
  {
    $this->copyFileAndFolders(
      [self::$schemaFileBackup],
      [self::$schemaFile]
    );

    $databaseClass = new ReflectionClass(Database::class);
    $_databaseFile = $databaseClass->getProperty('_databaseFile');
    $_databaseFile->setAccessible(true);

    // Launching the tasks
    Database::createDatabase(self::$databaseName);
    Database::truncateTable(self::$databaseName, self::$databaseFirstTableName);
  }

  /**
   * @author                   Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage The file "blabla" doesn't exist !
   */
  public function testExecuteFile_DoesNotExist() { Database::executeFile('blabla'); }

  /**
   * @author Lionel Péramo
   */
  public function testExecuteFile_Exists()
  {
    echo _DIR_;
    //    Database::executeFile();
  }



  /**
   * @author Lionel Péramo
   * depends on testInit, testInitCommand
   */
  public function testExecuteFixture()
  {
    // context
    $this->copyFileAndFolders(
      [
        self::$schemaFileBackup,
        self::$configFolderSqlFixturesBackup
      ],
      [
        self::$schemaFile,
        self::$configFolderSqlFixtures
      ]
    );

    // context - We truncate the tables.
    Sql::getDb();
    Sql::$instance->beginTransaction();

    try
    {
      Sql::$instance->query('USE ' . self::$databaseName);
      Sql::$instance->query('SET FOREIGN_KEY_CHECKS = 0');

      foreach (self::$tablesOrder as &$tableName)
      {
        Sql::$instance->query('TRUNCATE TABLE ' . $tableName);
      }

      $dbConfig = Sql::$instance->query('SET FOREIGN_KEY_CHECKS = 1');
      Sql::$instance->freeResult($dbConfig);
      Sql::$instance->commit();
    } catch(Exception $e)
    {
      Sql::$instance->rollBack();
      throw new LionelException($e->getMessage());
    }

    //    $schema = Yaml::parse(file_get_contents(self::$schemaFile));
    //    $fixturesData = Yaml::parse(file_get_contents(self::$configFolderYmlFixtures . self::$tablesOrder[0] . '.yml'));
    //die;
    $fixturesMemory = [];
    Database::init(self::$databaseConnection);

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

    $_executeFixture = new ReflectionMethod(Database::class, '_executeFixture');
    $_executeFixture->setAccessible(true);
    $_executeFixture->invokeArgs(null, [self::$databaseName, self::$tablesOrder[0]]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testDropDatabase()
  {
    define('VERBOSE', 2);
    Database::dropDatabase(self::$databaseName);
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage The file 'tests/bundles/core/config/data/yml/Schema.yml' doesn't exist. We can't generate the SQL schema without it.
   *
   * depends on testInitBase
   */
  public function testGenerateSqlSchema_NoSchema()
  {
    // Creating the context
    Database::initBase();

    // launching the task
    Database::generateSqlSchema(self::$databaseName);
  }

  /**
   * @author Lionel Péramo
   * depends on testInitBase
   */
  public function testGenerateSqlSchema_DontForce()
  {
    // Creating the context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaFile]);
    Database::initBase();

    // launching the task
    Database::generateSqlSchema(self::$databaseName);
  }

  /**
   * @author Lionel Péramo
   * depends on testInitBase
   */
  public function testGenerateSqlSchema_Force()
  {
    // Creating the context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaFile]);
    Database::initBase();

    // launching the task
    Database::generateSqlSchema(self::$databaseName, true);
  }

  /**
   * @author Lionel Péramo
   *
   * TODO Create a test fixture file in order to test that function !
   */
  public function testAnalyzeFixtures()
  {
    // context
    $this->copyFileAndFolders([self::$configFolderYmlFixturesBackup], [self::$configFolderYmlFixtures]);

    // launching the task
    $_analyzeFixtures = new ReflectionMethod(Database::class, '_analyzeFixtures');
    $_analyzeFixtures->setAccessible(true);
    $_analyzeFixtures->invokeArgs(null, [self::$configFolderYmlFixtures . self::$databaseFirstTableName . '.yml']);
  }

  /**
   * @author Lionel Péramo
   */
  public function testInitImports_AllNull()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = $database = null;
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage The database 'testDB' doesn't exist.
   */
  public function testInitImports_DatabaseNull()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = self::$databaseConnection;
    $database = null;
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testInitImports_NoNull()
  {
    // context
    $this->copyFileAndFolders([self::$schemaFileBackup], [self::$schemaFile]);

    try
    {
      Database::createDatabase(self::$databaseName);
    } catch(LionelException $le)
    {
      echo 'Schema already exists', PHP_EOL;
    }

    // launching the task
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = self::$databaseConnection;
    $database = All_Config::$dbConnections[$confToUse]['db'];
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author                   Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage The database 'noBDD' doesn't exist.
   */
  public function testInitImports_BadDatabase()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = self::$databaseConnection;
    $database = 'noBDD';
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testImportSchema()
  {
    Database::importSchema(self::$databaseName, self::$databaseConnection);
  }

  /**
   * @author Lionel Péramo
   * depends on testInit, testInitImports
   */
  public function testImportFixtures()
  {
    //context
    $this->copyFileAndFolders(
      [self::$configFolderYmlBackup . self::$tablesOrderFile . '.yml'],
      [self::$configFolderYml . self::$tablesOrderFile . '.yml']
    );

    // launching the task
    Database::importFixtures(self::$databaseName, self::$databaseConnection);
  }
}

