<?php
declare(strict_types=1);

namespace src\database;

use Exception;
use PHPUnit\Framework\TestCase;
use otra\{bdd\Pdomysql, bdd\Sql, OtraException};
use const otra\cache\php\{APP_ENV,PROD,TEST_PATH};

/**
 * The majority of the code is already tested by SqlTest.php, so we only test the remaining uncovered code.
 *
 * @runTestsInSeparateProcesses
 */
class PdomysqlTest extends TestCase
{
  private const string
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    GENERIC_QUERY = 'SELECT 1 FROM OtraTestTable';

  private static string $databaseName = 'testDB';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'test';
    $_SERVER['APP_SCOPE'] = 'local';
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (isset($_SESSION['bootstrap']))
      unset($_SESSION['bootstrap']);

    $_SERVER[APP_ENV] = 'test';

    try
    {
      Sql::getDb()->__destruct();
    } catch (Exception)
    {
      // If it crashes, it means that there is no default connection and probably no instance to destruct!
    }
  }

  /**
   * @throws OtraException
   */
  private function createDatabaseForTest() : void
  {
    require self::TEST_CONFIG_GOOD_PATH;

    Sql::getDb(null, false);
    Sql::$instance->beginTransaction();
    $dbResult = Sql::$instance->query(
      'CREATE DATABASE IF NOT EXISTS `' . self::$databaseName . '`; USE `' . self::$databaseName . '`;'
    );
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
    Sql::getDb();
    Sql::$instance->freeResult(
      Sql::$instance->query('CREATE TEMPORARY TABLE OtraTestTable (`a` VARCHAR(50));')
    );

    self::assertFalse(Sql::$instance->valuesOneCol(Sql::$instance->query(self::GENERIC_QUERY)));
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
    Sql::getDb();
    Sql::$instance->freeResult(
      Sql::$instance->query('CREATE TEMPORARY TABLE OtraTestTable (`a` VARCHAR(50));')
    );

    self::assertFalse(Sql::$instance->single(Sql::$instance->query(self::GENERIC_QUERY)));
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
    Sql::getDb();

    // testing
    self::assertTrue(Pdomysql::close(Sql::$instance));
    self::assertNull(Sql::$instance);
  }
}
