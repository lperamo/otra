<?
use phpunit\framework\TestCase;
use lib\myLibs\bdd\Sql;
use config\AllConfig;
use lib\myLibs\LionelException;

/**
 * @runTestsInSeparateProcesses
 */
class SqlTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
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
   * @author                         Lionel Péramo
   * @expectedException              \lib\myLibs\LionelException
   * @expectedExceptionMessageRegExp @This SGBD 'test' doesn't exist...yet ! Available SGBD are : (?:\w|,|\s)*@
   * @expectedExceptionCode          E_CORE_ERROR
   */
  public function testConstructBadSGBD()
  {
    new Sql('test');
  }

  /**
   * @author Lionel Péramo
   */
  public function test__Construct()
  {
    new Sql('PDOMySql');

    $this->assertEquals(
      removesFieldScopeProtection(Sql::class, '_currentSGBD')->getValue(),
      'lib\\myLibs\\bdd\\Pdomysql'
    );
  }

  /**
   * @author Lionel Péramo
   * depends on testConstruct
   */
  public function test__Destruct()
  {
    $sql = new Sql('PDOMySql');
    $this->assertInstanceOf(\lib\myLibs\bdd\Sql::class, $sql);
    $sql->__destruct($sql);
  }

  /**
   * @author Lionel Péramo
   */
  public function testGetDB()
  {
    $sqlInstance = Sql::getDB();
    $this->assertInstanceOf(Sql::class, $sqlInstance);
  }

  /**
   * @param string $fetchMethod
   *
   * @return
   *
   * @throws LionelException
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
   * @author Lionel Péramo
   * depends on testConstruct, AllConfig
   */
  public function testConnect()
  {
    $sql = new Sql('PDOMySql');
    extract(AllConfig::$dbConnections[AllConfig::$defaultConn]);

    /**
     *  Extractions give those variables
     * @type string $db
     * @type int    $port
     * @type string $host
     * @type string $login
     * @type string $password
     */

    $PDOInstance = $sql->connect('mysql:dbname=' . $db . ';host=' .
      ('' == $port ? $host : $host . ':' . $port), $login, $password);

    $this->assertInstanceOf(PDO::class, $PDOInstance);
  }

  /**
   * @author Lionel Péramo
    * depends on testGetDb
   */
  public function testQuery()
  {
    SQL::getDB();
    $this->assertInstanceOf(\PDOStatement::class, SQL::$instance->query('SELECT 1'));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchArray()
  {
    $this->assertInternalType('array', $this->fetch('fetchArray'));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchAssoc()
  {
    $this->assertInternalType('array', $this->fetch('fetchAssoc'));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchRow()
  {
    $this->assertInternalType('array', $this->fetch('fetchRow'));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testGetColumnMeta()
  {
    $this->assertInternalType('array', $this->fetch('getColumnMeta', 0));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchObject()
  {
    $this->assertInternalType('object', $this->fetch('fetchObject'));
  }

  // Already tested with the fetched methods !
  //public function testFreeResult(){}

  /**
   * @author Lionel Péramo
   */
  public function testLastInsertedId()
  {
    Sql::getDB();
    $this->assertInternalType('int', Sql::$instance->lastInsertedId());
  }

  /**
   * @author Lionel Péramo
   * @expectedException \lib\myLibs\LionelException
   * @expectedExceptionMessage This function does not exist with PDO and mysql driver is now deprecated !
   *
   * @throws LionelException
   */
  public function testSelectDB()
  {
    try
    {
      $sql = new SQL('Pdomysql');
      $sql->selectDb();
    }catch(Exception $e)
    {
      throw new LionelException('This function does not exist with PDO and mysql driver is now deprecated !');
    }
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB
   */
  public function testQuote()
  {
    Sql::getDB();
    $this->expectOutputString('Test \\\' string');
    echo Sql::$instance->quote('Test \' string');
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testQuery
   */
  public function testSingle()
  {
    SQL::getDB();
    $this->assertInternalType('string', Sql::$instance->single(Sql::$instance->query('SELECT 1')));
  }


  /**
   * @author Lionel Péramo
   * depends on testGetDB, testQuery
   */
  public function testValues()
  {
    SQL::getDB();
    $this->assertInternalType('array', Sql::$instance->values(Sql::$instance->query('SELECT 1,2')));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testQuery
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testValuesOneCol()
  {
    SQL::getDB();
    Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1'));
  }
}
