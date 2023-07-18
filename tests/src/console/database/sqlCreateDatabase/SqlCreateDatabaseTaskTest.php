<?php
declare(strict_types=1);

namespace src\console\database\sqlCreateDatabase;

use otra\config\AllConfig;
use otra\console\database\Database;
use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BUNDLES_PATH, CORE_PATH,PROD,TEST_PATH};
use function otra\tools\
{copyFileAndFolders, files\returnLegiblePath, setScopeProtectedFields};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SqlCreateDatabaseTaskTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CREATE_DATABASE = 'sqlCreateDatabase',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_NAME = 'testDB',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    DATABASE_SCHEMA_FORCE_SQL = self::CONFIG_FOLDER_SQL . 'database_schema_force.sql',
    OTRA_BINARY = 'otra.php',
    SCHEMA_FILE = 'schema.yml',
    SCHEMA_FILE_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/' . self::SCHEMA_FILE,
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE,
    TABLES_ORDER_FILE_PATH = self::CONFIG_FOLDER_YML . 'tables_order.yml';

  /**
   * @throws ReflectionException
   * @throws OtraException
   */
  public function testSqlCreateDatabaseTask() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders(
      [self::SCHEMA_FILE_BACKUP],
      [self::SCHEMA_ABSOLUTE_PATH]
    );
    require self::TEST_CONFIG_GOOD_PATH;
    require CORE_PATH . 'tools/files/returnLegiblePath.php';

    // clean up the SQL that force database schema creation if it exists
    if (file_exists(self::DATABASE_SCHEMA_FORCE_SQL))
      unlink(self::DATABASE_SCHEMA_FORCE_SQL);

    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
    $helloWorldBundleFolderExists = $bundlesFolderExists = true;

    if (!file_exists(BUNDLES_PATH))
    {
      $bundlesFolderExists = false;
      mkdir(BUNDLES_PATH);

      define(__NAMESPACE__ . '\\HELLO_WORLD_PATH', BUNDLES_PATH . 'HelloWorld');

      if (!file_exists(HELLO_WORLD_PATH))
      {
        $helloWorldBundleFolderExists = false;
        mkdir(HELLO_WORLD_PATH);
      }
    }

    Database::init();

    setScopeProtectedFields(
      Database::class,
      [
        'folder' => 'tests/src/bundles/',
        'pathSql' => self::CONFIG_FOLDER_SQL,
        'schemaFile' => self::SCHEMA_ABSOLUTE_PATH,
        'tablesOrderFile' => self::TABLES_ORDER_FILE_PATH
      ]
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_SQL_CREATE_DATABASE,
      [self::OTRA_BINARY, self::OTRA_TASK_SQL_CREATE_DATABASE, self::DATABASE_NAME, 'true']
    );

    // Testing
    $endPath = (new ReflectionClass(Database::class))->getProperty('databaseFile')->getValue()
      . '_force.sql';
    define(__NAMESPACE__ . '\\SCHEMA_FORCE_PATH', self::CONFIG_FOLDER_SQL . $endPath);
    self::assertFileEquals(
      self::CONFIG_FOLDER_SQL_BACKUP . $endPath,
      SCHEMA_FORCE_PATH,
      'Comparing ' . returnLegiblePath(SCHEMA_FORCE_PATH) . ' against (expected) ' .
      returnLegiblePath(self::CONFIG_FOLDER_SQL_BACKUP . $endPath)
    );

    // cleaning
    if (!$helloWorldBundleFolderExists)
      rmdir(HELLO_WORLD_PATH);

    if (!$bundlesFolderExists)
      rmdir(BUNDLES_PATH);

    unlink(SCHEMA_FORCE_PATH);
    unlink(self::SCHEMA_ABSOLUTE_PATH);
    unlink(self::TABLES_ORDER_FILE_PATH);
  }
}
