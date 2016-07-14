<?
use phpunit\framework\TestCase;
use lib\myLibs\console\Database;
use config\All_Config;
use lib\myLibs\Lionel_Exception;

/**
 * @runTestsInSeparateProcesses
 */
class DatabaseTest extends TestCase
{
  private static $configFolder = BASE_PATH . 'tests/bundles/core/config/';
  /**
   * Removes all files and folders specified in the array.
   *
   * @param array $fileOrFolders
   */
  private function cleanFileAndFolders(array $fileOrFolders)
  {
    foreach($fileOrFolders as &$folder)
    {
      if (true === file_exists($folder))
      {
        $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach($files as &$file)
        {
          {(true === $file->isDir()) ? 'rmdir' : 'unlink';}($file->getRealPath());
        }

        rmdir($folder);
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
   * @ expectedException              \lib\myLibs\Lionel_Exception
   * @ expectedExceptionMessageRegExp @This SGBD 'test' doesn't exist...yet ! Available SGBD are : (?:\w|,|\s)*@
   * @ expectedExceptionCode          E_CORE_ERROR
   *
   * depends on testInitBase
   */
  public function testInit() { Database::init(); }

  /**
   * @author Lionel Péramo
   */
  public function testInitCommand()
  {
    define('VERBOSE', 2);
    Database::initCommand();
  }

  /**
   * @author Lionel Péramo
   */
  public function testGetDirs() { Database::getDirs(false, 'tests/'); }

  /**
   * @author Lionel Péramo
   */
  public function testClean() { Database::clean(); }

  /**
   * @author Lionel Péramo
   * @throws Lionel_Exception If the original YAML schema can't be copied.
   * depends on testInit, testInitCommand
   */
  public function testCreateDatabase()
  {
    // Creating the context
    $this->cleanFileAndFolders([
      self::$configFolder . 'sql',
      self::$configFolder . 'yml/fixtures/tables_order.yml',
      self::$configFolder . 'yml/fixtures/ids'
    ]);

    if (false === copy(self::$configFolder . 'data/ymlBackup/Schema.yml', self::$configFolder . 'data/yml/Schema.yml'))
      throw new Lionel_Exception('Cannot retrieve the backup of the YAML schema !', E_CORE_ERROR);

    // Launching the task

    Database::createDatabase('testDB');
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
    Database::createFixture('test', 'test', [], [], [], $sortedTables, 'testFile');
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateFixtures_TruncateOnly() { Database::createFixtures('test', 1); }

  /**
   * @author Lionel Péramo
   */
  public function testCreateFixtures_CleanAndTruncate() { Database::createFixtures('test', 2); }


  /**
   * @author                   Lionel Péramo
   * @expectedException        \lib\myLibs\Lionel_Exception
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
    define('VERBOSE', 2);
    Database::executeFixture('test', 'test');
  }

  /**
   * @author Lionel Péramo
   */
  public function testDropDatabase()
  {
    define('VERBOSE', 2);
    Database::dropDatabase('test');
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\Lionel_Exception
   * @expectedExceptionMessage The file 'schema.yml' doesn't exist. We can't generate the SQL schema without it.
   */
  public function testGenerateSqlSchema_DontForce() { Database::generateSqlSchema('test'); }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\Lionel_Exception
   * @expectedExceptionMessage The file 'schema.yml' doesn't exist. We can't generate the SQL
   */
  public function testGenerateSqlSchema_Force() { Database::generateSqlSchema('test', true); }

  /**
   * @author Lionel Péramo
   * depends on testInitBase
   */
  public function testTruncateTable()
  {
    define('VERBOSE', 2);
    Database::truncateTable('test', 'test');
  }

  /**
   * @author Lionel Péramo
   *
   * TODO Create a test fixture file in order to test that function !
   */
  public function testAnalyzeFixtures()
  {
    $_analyzeFixtures = new ReflectionMethod(Database::class, '_analyzeFixtures');
    $_analyzeFixtures->setAccessible(true);
    $_analyzeFixtures->invokeArgs(null, ['test']);
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
   */
  public function testInitImports_DatabaseNull()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = key(All_Config::$dbConnections);
    $database = null;
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testInitImports_NoNull()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = key(All_Config::$dbConnections);
    $database = All_Config::$dbConnections[$confToUse]['db'];
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author                   Lionel Péramo
   * @expectedException        lib/myLibs/Lionel_Exception
   * @expectedExceptionMessage The database 'noBDD' doesn't exist.
   */
  public function testInitImports_BadDatabase()
  {
    $_initImports = new ReflectionMethod(Database::class, '_initImports');
    $_initImports->setAccessible(true);
    $confToUse = key(All_Config::$dbConnections);
    $database = 'noBDD';
    $_initImports->invokeArgs(null, [&$database, &$confToUse]);
  }

  /**
   * @author Lionel Péramo
   */
  public function testImportSchema() { Database::importSchema(); }

  /**
   * @author Lionel Péramo
   * depends on testInit, testInitImports
   */
  public function testImportFixtures() { Database::importFixtures(); }
}
