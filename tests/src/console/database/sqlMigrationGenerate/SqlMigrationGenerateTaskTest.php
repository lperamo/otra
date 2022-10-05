<?php
declare(strict_types=1);

namespace src\console\database\sqlMigrationGenerate;

use otra\bdd\Sql;
use otra\config\AllConfig;
use otra\OtraException;
use phpunit\framework\TestCase;
use ReflectionException;
use const otra\cache\php\{APP_ENV, BASE_PATH, CONSOLE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, SUCCESS};
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
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_SQL_MIGRATION_GENERATE = 'sqlMigrationGenerate',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    MIGRATIONS_FOLDER = BASE_PATH . 'migrations/',
    MIGRATIONS_VERSION_ZERO = self::MIGRATIONS_FOLDER  . 'version0.php';

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
    $database = Sql::getDb(self::DATABASE_CONNECTION, false);
    $database->query('CREATE DATABASE IF NOT EXISTS testDB;');
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function test_noMigrationsFolder() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TASK_SQL_MIGRATION_GENERATE, self::DATABASE_CONNECTION]);

    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      'Migrations folder created' . CLI_SUCCESS . ' ✔' . CLI_BASE . PHP_EOL .
      'The new blank migration file ' . CLI_INFO_HIGHLIGHT . returnLegiblePath(NEW_VERSION_FILE) . CLI_BASE .
      ' has been generated' . SUCCESS
    );

    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_VERSION_ZERO);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function test_migrationsFolder() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER, 0755);

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TASK_SQL_MIGRATION_GENERATE, self::DATABASE_CONNECTION]);

    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      'The new blank migration file ' . CLI_INFO_HIGHLIGHT . returnLegiblePath(NEW_VERSION_FILE) . CLI_BASE .
      ' has been generated' . SUCCESS
    );

    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_VERSION_ZERO);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function test_migrationFileAlreadyExists() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER, 0755);
    touch(self::MIGRATIONS_VERSION_ZERO);

    // testing exception
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);

    // launching task
    require CONSOLE_PATH . 'database/sqlMigrationGenerate/sqlMigrationGenerateTask.php';
    sqlMigrationGenerate([self::OTRA_BINARY, self::OTRA_TASK_SQL_MIGRATION_GENERATE, self::DATABASE_CONNECTION]);

    // testing
    self::expectOutputString(
      CLI_BASE . 'We generate the migrations table if it does not exist.' . PHP_EOL .
      CLI_ERROR . 'The new migration file ' . CLI_INFO_HIGHLIGHT . returnLegiblePath(NEW_VERSION_FILE) .
      CLI_ERROR . ' cannot be created as it already exists!' . PHP_EOL
    );

    self::assertDirectoryExists(self::MIGRATIONS_FOLDER);
    self::assertFileExists(self::MIGRATIONS_FOLDER  . 'version0.php');
  }
}
