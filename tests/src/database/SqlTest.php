<?php
declare(strict_types=1);

namespace src\database;

use Exception;
use PDOStatement;
use phpunit\framework\TestCase;
use otra\{bdd\Sql,OtraException};
use ReflectionException;
use ReflectionMethod;
use TypeError;
use const otra\cache\php\{APP_ENV, BASE_PATH, DEV, OTRA_PROJECT, PROD, TEST_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class SqlTest extends TestCase
{
  private const TEST_CONFIG_PATH = TEST_PATH . 'config/AllConfig.php',
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    TEST_CONFIG_BAD_DRIVER_PATH = TEST_PATH . 'config/AllConfigBadDriver.php',
    LOG_PATH = BASE_PATH . 'logs/',
    QUERY_SELECT_1 = 'SELECT 1';

  private static string $databaseName = 'testDB';

  protected function setUp() : void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (isset($_SESSION['bootstrap']))
      unset($_SESSION['bootstrap']);

    $_SERVER[APP_ENV] = PROD;

    try {
      Sql::getDb()->__destruct();
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

    Sql::getDb(null, false);
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
    $sqlInstance = Sql::getDb(null, false);
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
    Sql::getDb();

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
    Sql::getDb();
    self::assertInstanceOf(PDOStatement::class, Sql::$instance->query(self::QUERY_SELECT_1));
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
    Sql::getDb();
    self::assertEquals(null, Sql::$instance->query(self::QUERY_SELECT_1));
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
    $_SERVER[APP_ENV] = DEV;

    $devLogFolder = self::LOG_PATH . 'dev/';

    if (!file_exists($devLogFolder))
      mkdir($devLogFolder, 0777, true);

    $sqlLogPath = $devLogFolder . 'sql.txt';

    if (!file_exists($sqlLogPath))
      touch($sqlLogPath);

    // launching task
    Sql::getDb();
    $sqlLogContent = file_get_contents($sqlLogPath);
    self::assertInstanceOf(PDOStatement::class, Sql::$instance->query(self::QUERY_SELECT_1));
    self::assertEquals(
      $sqlLogContent
        . ($sqlLogContent !== ''
        ? ''
        : '[')
      . '{"file":"phar:///var/www/html/lib/phpunit.phar/phpunit/Framework/TestCase.php","line":1248,"query":"SELECT 1"},',
      file_get_contents($sqlLogPath)
    );

    if (!OTRA_PROJECT)
      file_put_contents($sqlLogPath, '');
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

    $this->expectException(TypeError::class);
    $this->expectExceptionMessage(
      'call_user_func_array(): Argument #1 ($callback) must be a valid callback, class otra\bdd\Pdomysql does not have a method "selectDb"'
    );

    $sqlInstance = Sql::getDb('test');
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

    $sqlInstance = Sql::getDb('testOtherDriver');
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
    Sql::getDb();
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
    Sql::getDb();
    self::assertIsInt(Sql::$instance->single(Sql::$instance->query(self::QUERY_SELECT_1)));
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
    Sql::getDb();
    self::assertNull(Sql::$instance->single(Sql::$instance->query(self::QUERY_SELECT_1)));
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
    Sql::getDb();
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
    Sql::getDb();
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
    Sql::getDb();
    self::assertEquals([1], Sql::$instance->valuesOneCol(Sql::$instance->query(self::QUERY_SELECT_1)));
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
    Sql::getDb();
    self::assertNull(Sql::$instance->valuesOneCol(Sql::$instance->query(self::QUERY_SELECT_1)));
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

    $sqlInstance = Sql::getDb();
    $sqlInstance->__destruct();

    self::assertEquals(null, Sql::$currentConn);
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   *
   * @depends testGetDB
   */
  public function testGetDB_AlreadyExistingConnection() : void
  {
    // Creating the context (having an SQL connection active named 'test')
    require self::TEST_CONFIG_GOOD_PATH;
    $sqlInstance = Sql::getDb('test');

    // Launching the task
    $sqlInstance2 = Sql::getDb('test');

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
    // context
    require TEST_PATH . 'config/AllConfigNoDefaultConnection.php';

    // assertions
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('There is no default connection in your configuration ! Check your configuration.');

    // Launching the task
    Sql::getDb();
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
    Sql::getDb('test');
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
    Sql::getDb();
    $sqlInstance = Sql::$instance->query(self::QUERY_SELECT_1);
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
    Sql::getDb();
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
    Sql::getDb();
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
    Sql::getDb();

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
   * @throws ReflectionException
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testClose_noInstance() : void
  {
    // context
    require self::TEST_CONFIG_GOOD_PATH;
    Sql::getDb();

    // testing
    Sql::$instance = null;
    self::assertFalse(
      (new ReflectionMethod(Sql::class, 'close'))->invokeArgs(null, [&Sql::$instance])
    );
  }
}
