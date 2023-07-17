<?php
declare(strict_types=1);

namespace src\console\database\sqlMigrationGenerate;

use otra\bdd\Sql;
use otra\config\AllConfig;
use otra\console\database\Database;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use const otra\cache\php\{APP_ENV, BASE_PATH, CONSOLE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_SUCCESS, SUCCESS};
use const otra\console\database\sqlMigrationGenerate\NEW_VERSION_FILE;
use function otra\console\database\sqlMigrationGenerate\sqlMigrationGenerate;
use function otra\tools\delTree;
use function otra\tools\files\returnLegiblePath;

/**
 * @runTestsInSeparateProcesses
 */
class SqlMigrationGenerateTaskTest extends TestCase
{
  private const
    DATABASE_CONNECTION = 'test',
    DATABASE_NAME = 'testDB',
    DATABASE_MIGRATION_TABLE = 'otra_migration_versions',
    OTRA_BINARY = 'otra.php',
    OTRA_TESTED_TASK = 'sqlMigrationGenerate',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    MIGRATIONS_FOLDER = BASE_PATH . 'migrations/',
    MIGRATIONS_VERSION_ZERO = self::MIGRATIONS_FOLDER  . 'version0.php',
    MIGRATIONS_VERSION_ONE = self::MIGRATIONS_FOLDER  . 'version1.php';

  private static $database;

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CORE_PATH . 'tools/deleteTree.php';

    if (file_exists(self::MIGRATIONS_FOLDER))
      delTree(self::MIGRATIONS_FOLDER);

  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (file_exists(self::MIGRATIONS_FOLDER))
      delTree(self::MIGRATIONS_FOLDER);

    self::$database->query('DROP DATABASE IF EXISTS testDB;');
    self::$database = null;
  }

  /**
   * Loads a main configuration specific to test purposes.
   *
   * @throws OtraException
   */
  private function loadConfig() : void
  {
    require(self::TEST_CONFIG_GOOD_PATH);
    AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
    AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
    self::$database = Sql::getDb(self::DATABASE_CONNECTION, false);
    self::$database->query('CREATE DATABASE IF NOT EXISTS testDB;');
  }

  /**
   * @throws OtraException
   */
  public function checkMigrationsTable() : void
  {
    if (!Database::$init)
      Database::init(self::DATABASE_CONNECTION);

    $queryMigrationTableExistence = self::$database->query(
      'SELECT TABLE_NAME
      FROM information_schema.TABLES 
      WHERE 
        TABLE_SCHEMA LIKE \'' . self::DATABASE_NAME . '\' AND 
	      TABLE_TYPE LIKE \'BASE TABLE\' AND
	      TABLE_NAME = \'' . self::DATABASE_MIGRATION_TABLE . '\';'
    );
    $doesMigrationTableExists = self::$database->single($queryMigrationTableExistence);
    self::assertSame('otra_migration_versions', $doesMigrationTableExists);
    self::$database->freeResult($queryMigrationTableExistence);
  }

  /**
   * Tests when the migrations folder does not exist
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testNoMigrationsFolder() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TESTED_TASK, self::DATABASE_CONNECTION]);

    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      'Migrations folder ' . returnLegiblePath(self::MIGRATIONS_FOLDER) . ' created' . CLI_SUCCESS . ' ✔' . CLI_BASE . PHP_EOL .
      'The new blank migration file ' . returnLegiblePath(NEW_VERSION_FILE) . CLI_BASE .
      ' has been generated' . SUCCESS
    );

    self::checkMigrationsTable();
    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_VERSION_ZERO);
  }

  /**
   * Tests when the migrations folder exists but does not contain any file
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testFirstMigrationFile() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER, 0755);

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TESTED_TASK, self::DATABASE_CONNECTION]);

    // testing
    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      'The new blank migration file ' . returnLegiblePath(self::MIGRATIONS_VERSION_ZERO) . CLI_BASE .
      ' has been generated' . SUCCESS
    );
    self::checkMigrationsTable();
    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_VERSION_ZERO);
  }

  /**
   * Tests when the folder exists and already contains one migration file
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testSecondMigrationFile() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER, 0755);
    touch(self::MIGRATIONS_VERSION_ZERO);

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TESTED_TASK, self::DATABASE_CONNECTION]);

    // testing
    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      'The new blank migration file ' .
      returnLegiblePath(self::MIGRATIONS_VERSION_ONE) . CLI_BASE .
      ' has been generated' . SUCCESS
    );
    self::checkMigrationsTable();
    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_VERSION_ZERO);
    self::assertFileExists(self::MIGRATIONS_VERSION_ONE);
  }
}
