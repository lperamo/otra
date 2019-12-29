<?php

use phpunit\framework\TestCase;
use config\AllConfig;
use lib\myLibs\{bdd\Sql,OtraException};

/**
 * @runTestsInSeparateProcesses
 */
class SqlTest extends TestCase
{
  private static string $databaseName = 'testDB';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
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
    require(BASE_PATH . 'tests/config/AllConfig.php');

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
    require(BASE_PATH . 'tests/config/AllConfig.php');

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
   * depends on testGetDb
   */
  public function testQuery()
  {
    // context
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
   * depends on testGetDB, testFreeResult, testQuery
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
   * depends on testGetDB, testFreeResult, testQuery
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
   * depends on testGetDB, testFreeResult, testQuery
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
   * depends on testGetDB, testFreeResult, testQuery
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
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchObject()
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    $this->assertIsObject($this->fetch('fetchObject'));
  }

  // Already tested with the fetched methods !
  //public function testFreeResult(){}

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
    require(BASE_PATH . 'tests/config/AllConfig.php');

    $this->expectException(OtraException :: class);
    $this->expectExceptionMessage('This function does not exist with \'PDOMySQL\'... and mysql driver is now deprecated !');

    $sql = Sql::getDB('test');
    $sql->selectDb();
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * depends on testGetDB
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
   * depends on testGetDB, testQuery
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
   * depends on testGetDB, testQuery
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
   * depends on testGetDB, testQuery
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
}
