<?php
declare(strict_types=1);

namespace src\console\architecture\createModule;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tools\delTree;

if (!defined(__NAMESPACE__ . '\\TEST_BUNDLE_UPPER'))
  define(__NAMESPACE__ . '\\TEST_BUNDLE_UPPER', ucfirst(CreateModuleTaskTest::TEST_BUNDLE));

/**
 * @runTestsInSeparateProcesses
 */
class CreateModuleTaskTest extends TestCase
{
  private const 
    TEST_TASK = 'createModule',
    TEST_BUNDLE_PATH = BUNDLES_PATH . TEST_BUNDLE_UPPER . DIR_SEPARATOR,
    TEST_MODULE_PATH = self::TEST_BUNDLE_PATH . CreateModuleTaskTest::TEST_MODULE . DIR_SEPARATOR,
    CREATE_BUNDLE_NO_INTERACTIVE_MODE = 'false',
    OTRA_BINARY_NAME = 'otra.php',
    CREATE_BUNDLE_FORCE = 'true';

  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (!OTRA_PROJECT && file_exists(self::TEST_BUNDLE_PATH))
    {
      require CORE_PATH . 'tools/deleteTree.php';
      delTree(self::TEST_BUNDLE_PATH);
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_NoBundlesFolder() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // testing exceptions
    self::expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    self::expectOutputString(
      CLI_ERROR . 'There is no ' . CLI_INFO_HIGHLIGHT . 'bundles' . CLI_ERROR .
      ' folder to put bundles! Please create this folder or launch ' . CLI_INFO_HIGHLIGHT . 'otra init' . CLI_ERROR .
      ' to solve it.' . END_COLOR . PHP_EOL
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_BundleDoNotExist_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // testing exceptions
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
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(CLI_ERROR . 'The bundle ' . CLI_INFO_HIGHLIGHT . TEST_BUNDLE_UPPER . CLI_ERROR .
      ' does not exist.' . END_COLOR . PHP_EOL);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateModuleTask_BundleDoNotExist_Force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE,
        self::CREATE_BUNDLE_FORCE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_MODULE_PATH);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_ModuleAlreadyExists_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_MODULE_PATH, 0777, true);

    // testing exceptions
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
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . 'bundles/' . TEST_BUNDLE_UPPER .
      DIR_SEPARATOR . self::TEST_MODULE . CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_ModuleAlreadyExists_Force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_MODULE_PATH, 0777, true);

    // testing exceptions
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
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . 'bundles/' . TEST_BUNDLE_UPPER .
      DIR_SEPARATOR . self::TEST_MODULE . CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateModuleTask_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(self::TEST_BUNDLE_PATH))
      mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_MODULE_PATH);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateModuleTask_Force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(self::TEST_BUNDLE_PATH))
      mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE,
        self::CREATE_BUNDLE_FORCE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_MODULE_PATH);
  }

}
