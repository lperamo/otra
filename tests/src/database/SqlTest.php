<?php
declare(strict_types=1);

namespace src\database;

use Exception;
use PDOStatement;
use phpunit\framework\TestCase;
use otra\{bdd\Sql,OtraException};

/**
 * @runTestsInSeparateProcesses
 */
class SqlTest extends TestCase
{
  private const TEST_CONFIG_PATH = TEST_PATH . 'config/AllConfig.php',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    TEST_CONFIG_BAD_DRIVER_PATH = TEST_PATH . 'config/AllConfigBadDriver.php',
    LOG_PATH = BASE_PATH . 'logs/';

  private static string $databaseName = 'testDB';

  protected function setUp() : void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'prod';
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (isset($_SESSION['bootstrap']) === true)
      unset($_SESSION['bootstrap']);

    $_SERVER[APP_ENV] = 'prod';

    try {
      Sql::getDB()->__destruct();
    } catch (Exception $exception) {
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
      Sql::$instance->query(/** @lang */'INSERT INTO CustomerData (`firstname`, `lastname`)
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
      Sql::$instance->query(/** @lang */'DROP TEMPORARY TABLE CustomerData')
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
    self::assertInstanceOf(Sql::class, $sqlInstance);
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

    $dbConfig = Sql::$instance->query(/** @lang */'SELECT `firstName`, `lastName` FROM CustomerData');

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
    self::assertInstanceOf(PDOStatement::class, Sql::$instance->query('SELECT 1'));
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
    self::assertEquals(null, Sql::$instance->query('SELECT 1'));
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
    $_SERVER[APP_ENV] = 'dev';

    $devLogFolder = self::LOG_PATH . 'dev/';

    if (file_exists($devLogFolder) === false)
      mkdir($devLogFolder, 0777, true);

    $sqlLogPath = $devLogFolder . 'sql.txt';

    if (file_exists($sqlLogPath) === false)
      touch($sqlLogPath);

    // launching task
    Sql::getDB();
    $sqlLogContent = file_get_contents($sqlLogPath);
    self::assertInstanceOf(PDOStatement::class, Sql::$instance->query('SELECT 1'));
    self::assertEquals(
      $sqlLogContent
        . '[{"file":"phar:///var/www/html/lib/phpunit.phar/phpunit/Framework/TestCase.php","line":1151,"query":"SELECT 1"},',
      file_get_contents($sqlLogPath)
    );

    if (OTRA_PROJECT === false)
    {
      unlink($sqlLogPath);
      rmdir($devLogFolder);
    }
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
    self::assertIsArray($this->fetch('fetchArray'));
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
    self::assertNull($this->fetch('fetchArray'));
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
    self::assertIsArray($this->fetch('fetchAssoc'));
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
    self::assertNull($this->fetch('fetchAssoc'));
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
    self::assertIsArray($this->fetch('fetchRow'));
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
    self::assertNull($this->fetch('fetchRow'));
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
    self::assertIsArray($this->fetch('getColumnMeta', 0));
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
    self::assertNull($this->fetch('getColumnMeta', 0));
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
    self::assertIsObject($this->fetch('fetchObject'));
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
    self::assertNull($this->fetch('fetchObject'));
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
    Sql::getDb();
    self::assertIsString(Sql::$instance->lastInsertedId());
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSelectDB_NoMethodSelectDb() : void
  {
    // loading the test configuration
    require self::TEST_CONFIG_GOOD_PATH;
    $this->createDatabaseForTest();

    $this->expectException(\TypeError::class);
    $this->expectExceptionMessage(
      'call_user_func_array() expects parameter 1 to be a valid callback, class \'otra\bdd\Pdomysql\' does not have a method \'selectDb\''
    );

    $sqlInstance = Sql::getDB('test');
    $sqlInstance->selectDb();
  }

  /**
   * @covers
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSelectDB_NoMethodSelectDbButOtherDriverThanPDOMySQL() : void
  {
    self::markTestIncomplete('We have to create more drivers classes to be able to test this condition');
    // loading the test configuration
    require BASE_PATH . self::TEST_CONFIG_GOOD_PATH;

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('This function does not exist with \'PDOMySQL\'.');

    $sqlInstance = Sql::getDB('testOtherDriver');
    $sqlInstance->selectDb();
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
    self::assertIsString(Sql::$instance->single(Sql::$instance->query('SELECT 1')));
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
    self::assertNull(Sql::$instance->single(Sql::$instance->query('SELECT 1')));
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
    self::assertIsArray(Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
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
    self::assertNull(Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
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
    self::assertEquals([1], Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1')));
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
    self::assertNull(Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1')));
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

    $sqlInstance = Sql::getDB();
    $sqlInstance->__destruct();

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
    $sqlInstance = Sql::getDB('test');

    // Launching the task
    $sqlInstance2 = Sql::getDB('test');

    // Testing !
    self::assertEquals($sqlInstance, $sqlInstance2);
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
    $sqlInstance = Sql::$instance->query('SELECT 1');
    self::assertNull(Sql::$instance->freeResult($sqlInstance));
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
    self::assertTrue(Sql::$instance->inTransaction());
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
    self::assertTrue(Sql::$instance->rollBack());
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
    } catch (Exception $exception)
    {
      self::assertEquals(
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
   * @throws \ReflectionException
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
    self::assertFalse(removeMethodScopeProtection(Sql::class, 'close')
      ->invokeArgs(null, [&Sql::$instance]));
  }
}
