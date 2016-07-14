<?
use phpunit\framework\TestCase;
use lib\myLibs\bdd\Sql;
use config\All_Config;
use lib\myLibs\Lionel_Exception;

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
   * @author                         Lionel Péramo
   * @expectedException              \lib\myLibs\Lionel_Exception
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
    $sql = new Sql('PDOMySql');

    $class = new ReflectionClass(Sql::class);
    $_currentSGBD = $class->getProperty('_currentSGBD');
    $_currentSGBD->setAccessible(true);

    $this->assertEquals($_currentSGBD->getValue(), 'lib\\myLibs\\bdd\\Pdomysql');
  }

  /**
   * @author Lionel Péramo
   * depends on testConstruct
   */
  public function test__Destruct()
  {
    $sql = new Sql('PDOMySql');
    $sql->__destruct($sql);
  }

  /**
   * @author Lionel Péramo
   */
  public function testGetDB()
  {
    Sql::getDB();
  }

  /**
   * @param string $fetchMethod
   */
  public function fetch(string $fetchMethod, $column = null)
  {
    session_start();
    Sql::getDB();
    $dbConfig = Sql::$instance->query('SELECT 1');

    if (null === $column)
      Sql::$instance->{$fetchMethod}($dbConfig);
    else
      Sql::$instance->{$fetchMethod}($dbConfig, $column);

    Sql::$instance->freeResult($dbConfig);
  }

  /**
   * @author Lionel Péramo
   * depends on testConstruct, All_Config
   */
  public function testConnect()
  {
    $sql = new Sql('PDOMySql');
    extract(All_Config::$dbConnections[All_Config::$defaultConn]);

    /**
     *  Extractions give those variables
     * @type string $db
     * @type int    $port
     * @type string $host
     * @type string $login
     * @type string $password
     */

    $sql->connect('mysql:dbname=' . $db . ';host=' .
      ('' == $port ? $host : $host . ':' . $port), $login, $password);
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
    $this->fetch('fetchArray');
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchAssoc()
  {
    $this->fetch('fetchAssoc');
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchRow()
  {
    $this->fetch('fetchRow');
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchField()
  {
    $this->fetch('fetchField', 0);
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testFreeResult, testQuery
   */
  public function testFetchObject()
  {
    $this->fetch('fetchObject');
  }

  // Already tested with the fetched methods !
  //public function testFreeResult(){}

  /**
   * @author Lionel Péramo
   */
  public function testLastInsertedId()
  {
    Sql::getDB();
    Sql::$instance->lastInsertedId();
  }

  /**
   * @author Lionel Péramo
   * @expectedException \lib\myLibs\Lionel_Exception
   * @expectedExceptionMessage This function does not exist with PDO and mysql driver is now deprecated !
   *
   * @throws Lionel_Exception
   */
  public function testSelectDB()
  {
    try
    {
      $sql = new SQL('Pdomysql');
      $sql->selectDb();
    }catch(Exception $e)
    {
      throw new Lionel_Exception('This function does not exist with PDO and mysql driver is now deprecated !');
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
    Sql::$instance->single(Sql::$instance->query('SELECT 1'));
  }


  /**
   * @author Lionel Péramo
   * depends on testGetDB, testQuery
   */
  public function testValues()
  {
    SQL::getDB();
    Sql::$instance->values(Sql::$instance->query('SELECT 1'));
  }

  /**
   * @author Lionel Péramo
   * depends on testGetDB, testQuery
   */
  public function testValuesOneCol()
  {
    SQL::getDB();
    Sql::$instance->valuesOneCol(Sql::$instance->query('SELECT 1'));
  }
}
