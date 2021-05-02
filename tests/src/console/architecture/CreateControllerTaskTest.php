<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tools\delTree;

/**
 * @runTestsInSeparateProcesses
 */
class CreateControllerTaskTest extends TestCase
{
  private static string 
    $testBundleUpper,
    $testBundlePath,
    $testModulePath,
    $testControllerPath;

  private const
    TEST_TASK = 'createController',
    OTRA_LABEL_FALSE = 'false',
    OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME = 'bundles/',
    OTRA_BINARY_NAME = 'otra.php';
  
  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;
  
  protected function setUp(): void
  {
    parent::setUp();
    self::$testBundleUpper = ucfirst(CreateControllerTaskTest::TEST_BUNDLE);
    self::$testBundlePath = BUNDLES_PATH . self::$testBundleUpper . DIR_SEPARATOR;
    self::$testModulePath = self::$testBundlePath . CreateControllerTaskTest::TEST_MODULE . DIR_SEPARATOR;
    self::$testControllerPath = self::$testControllerPath . 'controllers/' . CreateControllerTaskTest::TEST_CONTROLLER . DIR_SEPARATOR;
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    // cleaning
    if (!OTRA_PROJECT && file_exists(self::$testBundlePath))
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      delTree(self::$testBundlePath);
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_BundleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_ERROR . 'The bundle ' . CLI_INFO_HIGHLIGHT . self::$testBundleUpper . CLI_ERROR .
      ' does not exist.' . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::$testBundlePath, 0777, true);

    // assertions
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . self::OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME . self::$testBundleUpper .
      DIR_SEPARATOR . self::TEST_MODULE . CLI_ERROR . ' does not exist.' . END_COLOR . PHP_EOL);
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ControllerAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::$testControllerPath, 0777, true);

    // assertions
    $this->expectOutputString(CLI_ERROR . 'The controller ' . CLI_INFO_HIGHLIGHT . self::OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME . self::$testBundleUpper .
      DIR_SEPARATOR . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateControllerTask() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(self::$testControllerPath))
      mkdir(self::$testControllerPath, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(self::$testControllerPath);
  }
}
