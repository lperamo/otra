<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use function otra\console\deployment\genBootstrap\searchForClass;
use const otra\cache\php\{CONSOLE_PATH,CORE_PATH};
use const otra\console\{CLI_INFO, CLI_WARNING, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class SearchForClassTest extends TestCase
{
  private const
    TEST_CLASS = 'Controller',
    LABEL_TESTING_CLASS_FOUND = 'Testing $classFound...',
    CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE = 'otra\\console\\deployment\\genBootstrap\\VERBOSE';

  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testAlreadyParsed()
  {
    // context
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);

    // launching
    $classFound = searchForClass(
      ['otra\Controller'],
      self::TEST_CLASS,
      'class TestExtendsControllerNoNamespace extends otra\Controller',
      0
    );

    // testing
    self::assertFalse($classFound, self::LABEL_TESTING_CLASS_FOUND);
  }

  /**
   * @author Lionel Péramo
   */
  public function testNoNamespace()
  {
    // context
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);

    // launching
    $classFound = searchForClass(
      [],
      self::TEST_CLASS,
      'class TestExtendsControllerNoNamespace extends otra\Controller',
      0
    );

    // testing
    self::assertFalse($classFound, self::LABEL_TESTING_CLASS_FOUND);
  }

  /**
   * @author Lionel Péramo
   */
  public function testNotInClassMap_Verbose()
  {
    // context
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);

    // launching
    ob_start();
    $classFound = searchForClass(
      [],
      self::TEST_CLASS,
      'namespace test;class TestExtendsControllerNoNamespace extends Controller',
      0
    );

    // testing
    self::assertFalse($classFound, self::LABEL_TESTING_CLASS_FOUND);
    self::assertSame(
      CLI_WARNING . 'Notice : Please check if you use a class ' . CLI_INFO . self::TEST_CLASS . CLI_WARNING .
      ' in a use statement but this file seems to be not included ! Maybe the file name is only in a comment though.' .
      END_COLOR . PHP_EOL,
      ob_get_clean(),
      'Testing searchForClass output...'
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testNotInClassMap_NotVerbose()
  {
    // context
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 0);

    // launching
    ob_start();
    $classFound = searchForClass(
      [],
      self::TEST_CLASS,
      'namespace test;class TestExtendsControllerNoNamespace extends Controller',
      0
    );

    // testing
    self::assertFalse($classFound, self::LABEL_TESTING_CLASS_FOUND);
    self::assertSame(
      '',
      ob_get_clean(),
      'Testing searchForClass output...'
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testIsInClassMap()
  {
    // context
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);

    // launching
    $classFound = searchForClass(
      [],
      self::TEST_CLASS,
      'namespace otra;class TestExtendsControllerNoNamespace extends otra\Controller',
      54 // 'extends' position
    );

    // testing
    self::assertSame(
      CORE_PATH . 'Controller.php',
      $classFound,
      self::LABEL_TESTING_CLASS_FOUND
    );
  }
}
