<?php

use phpunit\framework\TestCase;
use lib\myLibs\{bdd\Sql, OtraException};

/**
 * The majority of the code is already tested by SqlTest.php so we only test the remaining uncovered code.
 *
 * @runTestsInSeparateProcesses
 */
class PdomysqlTest extends TestCase
{
  const TEST_CONFIG_PATH = BASE_PATH . 'tests/config/AllConfig.php';
  const TEST_CONFIG_GOOD_PATH = BASE_PATH . 'tests/config/AllConfigGood.php';
  const TEST_CONFIG_BAD_DRIVER_PATH = BASE_PATH . 'tests/config/AllConfigBadDriver.php';
  const TEST_CONFIG_NO_DEFAULT_CONNECTION = BASE_PATH . 'tests/config/AllConfigNoDefaultConnection.php';
  const LOG_PATH = BASE_PATH . 'logs/';

  private static string $databaseName = 'testDB';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  protected function tearDown(): void
  {
    if (isset($_SESSION['bootstrap']) === true)
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
  private function createDatabaseForTest() {
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
  public function testValuesOneCol_NoRows()
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
  public function testSingle_NoRows()
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
   * @throws ReflectionException
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testClose_InstancePassed()
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    Sql::getDB();

    // testing
    $this->assertTrue(\lib\myLibs\bdd\Pdomysql::close(Sql::$instance));
    $this->assertNull(Sql::$instance);
  }
}
