<?php

use phpunit\framework\TestCase;
use lib\otra\{bdd\Sql,OtraException};

/**
 * @runTestsInSeparateProcesses
 */
class SqlTest extends TestCase
{
  const TEST_CONFIG_PATH = BASE_PATH . 'tests/config/AllConfig.php';
  const TEST_CONFIG_GOOD_PATH = BASE_PATH . 'tests/config/AllConfigGood.php';
  const TEST_CONFIG_BAD_DRIVER_PATH = BASE_PATH . 'tests/config/AllConfigBadDriver.php';
  const TEST_CONFIG_NO_DEFAULT_CONNECTION = BASE_PATH . 'tests/config/AllConfigNoDefaultConnection.php';
  const LOG_PATH = BASE_PATH . 'logs/';

  private static string $databaseName = 'testDB';

  protected function setUp() : void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  protected function tearDown(): void
  {
    if (isset($_SESSION['bootstrap']) === true)
      unset($_SESSION['bootstrap']);

    $_SERVER['APP_ENV'] = 'prod';

    try {
      Sql::getDB()->__destruct();
    } catch (Exception $e) {
      // If it crashes, it means that there is no default connection and probably no instance to destruct !
    }
  }

  /**
   * Creates an empty temporary table that have to be removed later.
   */
  protected function createTemporaryTestTable() : void
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
  protected function createTemporaryTestValues() : void
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
  protected function dropTemporaryTestTable() : void
  {
    Sql::$instance->freeResult(
      Sql::$instance->query('DROP TEMPORARY TABLE CustomerData')
    );
  }

  /**
   * @throws OtraException
   */
  private function createDatabaseForTest() : void {
    require(self::TEST_CONFIG_GOOD_PATH);

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
  public function testGetDB() : void
  {
    // context
    require self::TEST_CONFIG_PATH;
    require self::TEST_CONFIG_GOOD_PATH;

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
  public function testQuery() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    $this->assertInstanceOf(\PDOStatement::class, Sql::$instance->query('SELECT 1'));
  }

  /**
   * @throws OtraException
   * @author  Lionel Péramo
   */
  public function testQuery_BootstrapKeyInSession() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    Sql::getDB();
    $this->assertEquals(null, Sql::$instance->query('SELECT 1'));
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testQuery_DevEnvironment() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    $this->createDatabaseForTest();
    $_SERVER['APP_ENV'] = 'dev';

    // launching task
    Sql::getDB();
    $sqlLogPath = self::LOG_PATH . 'dev/sql.txt';
    $sqlLogContent = file_get_contents($sqlLogPath);
    $this->assertInstanceOf(\PDOStatement::class, Sql::$instance->query('SELECT 1'));
    $this->assertEquals(
      $sqlLogContent
        . '{"file":"phar:///var/www/html/lib/phpunit.phar/phpunit/Framework/TestCase.php","line":1151,"query":"SELECT 1"},',
      file_get_contents($sqlLogPath)
    );
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchArray() : void
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
  public function testFetchArray_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    $this->assertNull($this->fetch('fetchArray'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchAssoc() : void
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
  public function testFetchAssoc_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    $this->assertNull($this->fetch('fetchAssoc'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchRow() : void
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
  public function testFetchRow_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    $this->assertNull($this->fetch('fetchRow'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testGetColumnMeta() : void
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
  public function testGetColumnMeta_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    $this->assertNull($this->fetch('getColumnMeta', 0));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchObject() : void
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
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testFetchObject_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    $this->assertNull($this->fetch('fetchObject'));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testLastInsertedId() : void
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
  public function testSelectDB_NoMethodSelectDb() : void
  {
    // loading the test configuration
    require self::TEST_CONFIG_GOOD_PATH;

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('This function does not exist with \'PDOMySQL\'... and mysql driver is now deprecated !');

    $sql = Sql::getDB('test');
    $sql->selectDb();
  }

  /**
   * @covers
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSelectDB_NoMethodSelectDbButOtherDriverThanPDOMySQL() : void
  {
    $this->markTestIncomplete('We have to create more drivers classes to be able to test this condition');
    // loading the test configuration
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('This function does not exist with \'PDOMySQL\'.');

    $sql = Sql::getDB('testOtherDriver');
    $sql->selectDb();
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testQuote() : void
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
  public function testSingle() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
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
  public function testSingle_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    Sql::getDB();
    $this->assertNull(Sql::$instance->single(Sql::$instance->query('SELECT 1')));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testValues() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    $this->assertIsArray(Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testValues_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    Sql::getDB();
    $this->assertNull(Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testValuesOneCol() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    $this->assertEquals([1], Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1')));
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   *
   * @depends testGetDB
   * @depends testQuery
   */
  public function testValuesOneCol_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    Sql::getDB();
    $this->assertNull(Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1')));
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function test__destruct() : void
  {
    require self::TEST_CONFIG_GOOD_PATH;

    $sql = Sql::getDB();
    $sql->__destruct();

    self::assertEquals(null, Sql::$_currentConn);
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDB_AlreadyExistingConnection() : void
  {
    // Creating the context (having a SQL connection active named 'test')
    require self::TEST_CONFIG_GOOD_PATH;
    $sql = Sql::getDB('test');

    // Launching the task
    $sql2 = Sql::getDB('test');

    // Testing !
    $this->assertEquals($sql, $sql2);
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDB_NoDefaultConnection() : void
  {
    // assertions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('There is no default connection in your configuration ! Check your configuration.');

    // Launching the task
    Sql::getDB();
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDB_DbmsNotAvailable() : void
  {
    // context
    require self::TEST_CONFIG_BAD_DRIVER_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('This DBMS \'Hello\' is not available...yet ! Available DBMS are : Pdomysql');

    // Launching the task
    Sql::getDB('test');
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testFreeResult_BootstrapKeyInSession() : void
  {
    // context
    $this->createDatabaseForTest();
    $_SESSION['bootstrap'] = true;

    // launching task
    Sql::getDB();
    $sql = Sql::$instance->query('SELECT 1');
    $this->assertNull(Sql::$instance->freeResult($sql));
  }

  /**
   * @author Lionel Péramo
   *
   * @throws OtraException
   */
  public function testInTransaction() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    Sql::$instance->beginTransaction();
    $this->assertTrue(Sql::$instance->inTransaction());
    Sql::$instance->commit();
  }

  /**
   * @author Lionel Péramo
   *
   * @throws OtraException
   */
  public function testRollBack() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();
    Sql::$instance->beginTransaction();
    $this->assertTrue(Sql::$instance->rollBack());
  }

  /**
   * @author Lionel Péramo
   *
   * @throws OtraException
   */
  public function testErrorInfo() : void
  {
    // context
    $this->createDatabaseForTest();

    // launching task
    Sql::getDB();

    try {
      Sql::$instance->query('bogus sql');
    } catch (Exception $e) {
      $this->assertEquals(
      [
        0 => '42000',
        1 => '1064',
        2 => 'You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'bogus sql\' at line 1'
      ],
        Sql::$instance->errorInfo()
      );
    }
  }

  /**
   * @throws ReflectionException
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testClose_noInstance() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    Sql::getDB();

    // testing
    Sql::$instance = null;
    $this->assertFalse(removeMethodScopeProtection(Sql::class, 'close')
      ->invokeArgs(null, [&Sql::$instance]));
  }
}
