<?php

use phpunit\framework\TestCase;
use lib\myLibs\{bdd\Sql,OtraException};

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SqlTest extends TestCase
{
  const TEST_CONFIG_PATH = 'tests/config/AllConfig.php';
  const TEST_CONFIG_GOOD_PATH = 'tests/config/AllConfigGood.php';
  const TEST_CONFIG_NO_DEFAULT_CONNECTION = 'tests/config/AllConfigNoDefaultConnection.php';
  private static string $databaseName = 'testDB';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  protected function tearDown(): void
  {
    try {
      Sql::getDB()->__destruct();
    } catch (Exception $e) {
      // If it crashes, it means that there is no default connection and probably no instance to destruct !
    }
  }

  /**
   * Creates an empty temporary table that have to be removed later.
   */
  protected function createTemporaryTestTable(): void
  {
    Sql::$instance->freeResult(
      Sql::$instance->query('CREATE TEMPORARY TABLE CustomerData (
        `firstName` VARCHAR(50),
        `lastName` VARCHAR(50)
      );      
      ')
    );
  }

  /**
   * Creates test fixtures into the empty temporary table that have to be removed later.
   */
  protected function createTemporaryTestValues(): void
  {
    Sql::$instance->freeResult(
      Sql::$instance->query('INSERT INTO CustomerData (`firstname`, `lastname`)
        VALUES ("john", "smith"),
        ("paul", "miller");
      ')
    );
  }

  /**
   * Drops the temporary table (with test fixtures) created by 'createTemporaryTestData' function,
   * that have to be removed.
   */
  protected function dropTemporaryTestTable(): void
  {
    Sql::$instance->freeResult(
      Sql::$instance->query('DROP TEMPORARY TABLE CustomerData')
    );
  }

  /**
   * @throws OtraException
   */
  private function createDatabaseForTest() {
    require(BASE_PATH . self::TEST_CONFIG_GOOD_PATH);

    Sql::getDB(null, false);
    Sql::$instance->beginTransaction();
    $dbResult = Sql::$instance->query('CREATE DATABASE IF NOT EXISTS `' . self::$databaseName . '`; USE ' . self::$databaseName . ';');
    Sql::$instance->freeResult($dbResult);
    Sql::$instance->commit();
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testGetDB()
  {
    // context
    require BASE_PATH . self::TEST_CONFIG_PATH;
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;

    // launching task
    $sqlInstance = Sql::getDB(null, false);
    $this->assertInstanceOf(Sql::class, $sqlInstance);
  }

  /**
   * @param string $fetchMethod
   * @param null   $column
   *
   * @return mixed
   *
   * @throws OtraException
   */
  public function fetch(string $fetchMethod, $column = null)
  {
    Sql::getDB();

    $this->createTemporaryTestTable();
    $this->createTemporaryTestValues();

    $dbConfig = Sql::$instance->query('SELECT `firstName`, `lastName` FROM CustomerData');

    $result = (null === $column)
      ? Sql::$instance->{$fetchMethod}($dbConfig)
      : Sql::$instance->{$fetchMethod}($dbConfig, $column);

    Sql::$instance->freeResult($dbConfig);

    $this->dropTemporaryTestTable();

    return $result;
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testQuery()
  {
    // context
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;
    $this->createDatabaseForTest();

    // launching task
    SQL::getDB();
    $this->assertInstanceOf(\PDOStatement::class, SQL::$instance->query('SELECT 1'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchArray()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsArray($this->fetch('fetchArray'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchAssoc()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsArray($this->fetch('fetchAssoc'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchRow()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsArray($this->fetch('fetchRow'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testGetColumnMeta()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsArray($this->fetch('getColumnMeta', 0));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchObject()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsObject($this->fetch('fetchObject'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testLastInsertedId()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    $this->assertIsInt(Sql::$instance->lastInsertedId());
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSelectDBNoMethodSelectDb()
  {
    // loading the test configuration
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('This function does not exist with \'PDOMySQL\'... and mysql driver is now deprecated !');

    $sql = Sql::getDB('test');
    $sql->selectDb();
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testQuote()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    $this->expectOutputString('Test \\\' string');
    echo Sql::$instance->quote('Test \' string');
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testSingle()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    SQL::getDB();
    $this->assertIsString(Sql::$instance->single(Sql::$instance->query('SELECT 1')));
  }


  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testValues()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    SQL::getDB();
    $this->assertIsArray(Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testValuesOneCol()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    SQL::getDB();
    Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1'));
  }

  /**
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function test__destruct()
  {
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;

    $sql = Sql::getDB();
    $sql->__destruct();

    self::assertEquals(null, Sql::$_currentConn);
  }

  /**
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDBAlreadyExistingConnection()
  {
    // Creating the context (having a SQL connection active named 'test')
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;
    $sql = Sql::getDB('test');

    // Launching the task
    $sql2 = Sql::getDB('test');

    // Testing !
    $this->assertEquals($sql, $sql2);
  }

  /**
   * @throws OtraException
   * @author  Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDBNoDefaultConnection()
  {
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('There is no default connection in your configuration ! Check your configuration.');

    // Launching the task
    $sql = Sql::getDB();
  }
}
