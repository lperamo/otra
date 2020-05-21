<?php
declare(strict_types=1);

namespace src\database;

use phpunit\framework\TestCase;
use otra\{bdd\Sql, OtraException};

/**
 * The majority of the code is already tested by SqlTest.php so we only test the remaining uncovered code.
 *
 * @runTestsInSeparateProcesses
 */
class PdomysqlTest extends TestCase
{
  const TEST_CONFIG_PATH = TEST_PATH . 'config/AllConfig.php';
  const TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php';
  const TEST_CONFIG_BAD_DRIVER_PATH = TEST_PATH . 'config/AllConfigBadDriver.php';
  const TEST_CONFIG_NO_DEFAULT_CONNECTION = TEST_PATH . 'config/AllConfigNoDefaultConnection.php';
  const LOG_PATH = BASE_PATH . 'logs/';

  private static string $databaseName = 'testDB';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER['APP_ENV'] = 'prod';
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (isset($_SESSION['bootstrap']))
      unset($_SESSION['bootstrap']);

    $_SERVER['APP_ENV'] = 'prod';

    try
    {
      Sql::getDB()->__destruct();
    } catch (Exception $e)
    {
      // If it crashes, it means that there is no default connection and probably no instance to destruct !
    }
  }

  /**
   * @throws OtraException
   */
  private function createDatabaseForTest() : void
  {
    require(self::TEST_CONFIG_GOOD_PATH);

    Sql::getDB(null, false);
    Sql::$instance->beginTransaction();
    $dbResult = Sql::$instance->query('CREATE DATABASE IF NOT EXISTS `' . self::$databaseName . '`; USE ' . self::$databaseName . ';');
    Sql::$instance->freeResult($dbResult);
    Sql::$instance->commit();
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testValuesOneCol_NoRows() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    Sql::$instance->freeResult(
      Sql::$instance->query('CREATE TEMPORARY TABLE OtraTestTable (`a` VARCHAR(50));')
    );

    $this->assertFalse(Sql::$instance->valuesOneCol(Sql::$instance->query("SELECT 1 FROM OtraTestTable")));
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSingle_NoRows() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    Sql::$instance->freeResult(
      Sql::$instance->query('CREATE TEMPORARY TABLE OtraTestTable (`a` VARCHAR(50));')
    );

    $this->assertFalse(Sql::$instance->single(Sql::$instance->query("SELECT 1 FROM OtraTestTable")));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testClose_InstancePassed() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    Sql::getDB();

    // testing
    $this->assertTrue(\otra\bdd\Pdomysql::close(Sql::$instance));
    $this->assertNull(Sql::$instance);
  }
}
