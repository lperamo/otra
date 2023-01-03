<?php
declare(strict_types=1);

namespace src\console\database\sqlMigrationExecute;

use otra\bdd\Sql;
use otra\config\AllConfig;
use otra\console\database\Database;
use otra\OtraException;
use phpunit\framework\TestCase;
use ReflectionException;
use function otra\tools\delTree;
use const otra\cache\php\{APP_ENV, BASE_PATH, CONSOLE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR, SUCCESS};
use const otra\console\database\sqlMigrationGenerate\NEW_VERSION_FILE;
use function otra\console\database\sqlMigrationExecute\sqlMigrationExecute;
use function otra\tools\files\returnLegiblePath;

/**
 * @runTestsInSeparateProcesses
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 */
class SqlMigrationExecuteTaskTest extends TestCase
{
  private const
    BAD_DATABASE_CONNECTION = 'badDBMS',
    DATABASE_CONNECTION = 'test',
    DATABASE_NAME = 'testDB',
    DATABASE_MIGRATION_TABLE = 'otra_migration_versions',
    EXAMPLE_PATH_BEGINNING = self::EXAMPLES_MIGRATIONS_FOLDER . 'version' . self::MIGRATION_VERSION_ZERO,
    EXAMPLE_NOT_AN_ARRAY = self::EXAMPLE_PATH_BEGINNING . 'NotAnArray.php',
    EXAMPLE_NO_VERSION = self::EXAMPLE_PATH_BEGINNING . 'NoVersion.php',
    EXAMPLE_NO_UP = self::EXAMPLE_PATH_BEGINNING . 'NoUp.php',
    EXAMPLE_NO_DOWN = self::EXAMPLE_PATH_BEGINNING . 'NoDown.php',
    EXAMPLES_MIGRATIONS_FOLDER = TEST_PATH . 'examples/' . self::OTRA_TESTED_TASK . '/',
    OTRA_BINARY = 'otra.php',
    OTRA_TESTED_TASK = 'sqlMigrationExecute',
    TASK_FILE = CONSOLE_PATH . 'database/' . self::OTRA_TESTED_TASK . '/' . self::OTRA_TESTED_TASK . 'Task.php',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    MIGRATIONS_FOLDER = BASE_PATH . 'migrations/',
    MIGRATION_FILE_VERSION_ZERO = self::MIGRATIONS_FOLDER  . 'version0.php',
    MIGRATION_VERSION_ZERO = '0',
    MIGRATION_UP = 'up',
    MIGRATION_DOWN = 'down';

  private static bool|Sql $database;

  protected function setUp(): void
  {
    parent::setUp();

    require CORE_PATH . 'tools/deleteTree.php';
    require CORE_PATH . 'tools/files/returnLegiblePath.php';

    if (file_exists(self::MIGRATIONS_FOLDER))
      delTree(self::MIGRATIONS_FOLDER);
  }

  /**
   * @throws OtraException
   */
  protected function tearDown(): void
  {
    parent::tearDown();

    if (file_exists(self::MIGRATIONS_FOLDER))
      delTree(self::MIGRATIONS_FOLDER);

    self::$database ??= Sql::getDb(self::DATABASE_CONNECTION, false);
    self::$database->query('DROP DATABASE IF EXISTS testDB;');
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
  private function loadAndTest(string $example, string $keyToTest) : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER);
    copy($example, self::MIGRATION_FILE_VERSION_ZERO);

    // testing
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(
      CLI_ERROR . 'The migration file ' . CLI_INFO_HIGHLIGHT .
      returnLegiblePath(self::MIGRATION_FILE_VERSION_ZERO) . CLI_ERROR . ' does not return the key ' .
      CLI_INFO_HIGHLIGHT . $keyToTest . CLI_ERROR . '.' . END_COLOR
    );
  }

  /**
   * Tests when the migrations folder does not exist
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testBadWay() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();

    // testing
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(
      CLI_ERROR . 'You must choose between ' . CLI_INFO_HIGHLIGHT . 'up' . CLI_ERROR . ' and ' . CLI_INFO_HIGHLIGHT .
      'down' . CLI_ERROR . '.' . END_COLOR
    );

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([self::OTRA_BINARY, self::OTRA_TESTED_TASK, '6', 'no']);
  }

  /**
   * @throws OtraException
   */
  public function testMigrationFileDoesNotExist(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();

    // testing
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(
      CLI_ERROR . 'The migration file ' . CLI_INFO_HIGHLIGHT .
      returnLegiblePath(self::MIGRATION_FILE_VERSION_ZERO) . CLI_ERROR . ' does not exist.' . END_COLOR
    );

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([self::OTRA_BINARY, self::OTRA_TESTED_TASK, self::MIGRATION_VERSION_ZERO, self::MIGRATION_UP]);
  }

  /**
   * @throws OtraException
   */
  public function testBadDbmsConnection(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER);
    touch(self::MIGRATION_FILE_VERSION_ZERO);

    // testing
    self::expectException(OtraException::class);
    self::expectExceptionCode(32);
    self::expectOutputRegex(
      "@.*The configuration '" . self::BAD_DATABASE_CONNECTION .
      "' does not exist in your configuration file.*@"
    );

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([
      self::OTRA_BINARY,
      self::OTRA_TESTED_TASK,
      self::MIGRATION_VERSION_ZERO,
      self::MIGRATION_UP,
      self::BAD_DATABASE_CONNECTION
    ]);
  }

  /**
   * @throws OtraException
   */
  public function testMigrationIsNotAnArray(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $this->loadConfig();
    mkdir(self::MIGRATIONS_FOLDER);
    copy(self::EXAMPLE_NOT_AN_ARRAY, self::MIGRATION_FILE_VERSION_ZERO);

    // testing
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(
      CLI_ERROR . 'The migration file ' . CLI_INFO_HIGHLIGHT .
      returnLegiblePath(self::MIGRATION_FILE_VERSION_ZERO) . CLI_ERROR . ' does not return an array.' . END_COLOR
    );

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([
      self::OTRA_BINARY,
      self::OTRA_TESTED_TASK,
      self::MIGRATION_VERSION_ZERO,
      self::MIGRATION_UP,
      self::DATABASE_CONNECTION
    ]);
  }

  /**
   * @throws OtraException
   */
  public function testMigrationHasNoVersionKey(): void
  {
    self::loadAndTest(self::EXAMPLE_NO_VERSION, 'version');

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([
      self::OTRA_BINARY,
      self::OTRA_TESTED_TASK,
      self::MIGRATION_VERSION_ZERO,
      self::MIGRATION_UP,
      self::DATABASE_CONNECTION
    ]);
  }

  /**
   * @throws OtraException
   */
  public function testMigrationHasNoUpKey(): void
  {
    self::loadAndTest(self::EXAMPLE_NO_UP, self::MIGRATION_UP);

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([
      self::OTRA_BINARY,
      self::OTRA_TESTED_TASK,
      self::MIGRATION_VERSION_ZERO,
      self::MIGRATION_UP,
      self::DATABASE_CONNECTION
    ]);
  }

  /**
   * @throws OtraException
   */
  public function testMigrationHasNoDownKey(): void
  {
    self::loadAndTest(self::EXAMPLE_NO_DOWN, self::MIGRATION_DOWN);

    // launching task
    require self::TASK_FILE;
    sqlMigrationExecute([
      self::OTRA_BINARY,
      self::OTRA_TESTED_TASK,
      self::MIGRATION_VERSION_ZERO,
      self::MIGRATION_DOWN,
      self::DATABASE_CONNECTION
    ]);
  }
}
