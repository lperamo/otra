<?php
declare(strict_types=1);

namespace src\console\architecture\createBundle;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tools\delTree;

if (!defined(__NAMESPACE__ . '\\TEST_BUNDLE_UPPER'))
  define(__NAMESPACE__ . '\\TEST_BUNDLE_UPPER', ucfirst(CreateBundleTaskTest::TEST_BUNDLE_NAME));

/**
 * @runTestsInSeparateProcesses
 */
class CreateBundleTaskTest extends TestCase
{
  final public const TEST_BUNDLE_NAME = 'test';
  private const
    TEST_TASK = 'createBundle',
    TEST_BUNDLE_PATH = BUNDLES_PATH . TEST_BUNDLE_UPPER . DIR_SEPARATOR,
    TEST_BUNDLE_CONFIG_PATH = self::TEST_BUNDLE_PATH . 'config/',
    TEST_BUNDLE_MODELS_PATH = self::TEST_BUNDLE_PATH . 'models/',
    TEST_BUNDLE_RESOURCES_PATH = self::TEST_BUNDLE_PATH . 'resources/',
    TEST_BUNDLE_VIEWS_PATH = self::TEST_BUNDLE_PATH . 'views/',
    CREATE_BUNDLE_MASK_FULL = '15',
    CREATE_BUNDLE_MASK_NOTHING = '0',
    CREATE_BUNDLE_NO_INTERACTIVE_MODE = 'false',
    CREATE_BUNDLE_FORCE = 'true',
    OTRA_BINARY = 'otra.php';

  // it fixes issues like when AllConfig is not loaded while it should be
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
   * Creates all the assertions when things should be created with no issues at all.
   */
  private function testAll() : void
  {
    self::assertFileExists(self::TEST_BUNDLE_PATH);
    self::assertFileExists(self::TEST_BUNDLE_CONFIG_PATH);
    self::assertFileExists(self::TEST_BUNDLE_MODELS_PATH);
    self::assertFileExists(self::TEST_BUNDLE_RESOURCES_PATH);
    self::assertFileExists(self::TEST_BUNDLE_VIEWS_PATH);
  }

  public function testCreateBundleTask_noBundlesFolder(): void
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
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::CREATE_BUNDLE_MASK_FULL,
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
   * @throws OtraException
   */
  public function testCreateBundleTask_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(BUNDLES_PATH, 0777, true);

    // testing exception

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::CREATE_BUNDLE_MASK_FULL,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->testAll();
  }

  /**
   * @throws OtraException
   */
  public function testCreateBundleTask_force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(BUNDLES_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::CREATE_BUNDLE_MASK_FULL,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE,
        self::CREATE_BUNDLE_FORCE
      ]
    );

    // testing
    $this->testAll();
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateBundleTask_BundleAlreadyExists_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_WARNING . 'The bundle ' . CLI_INFO_HIGHLIGHT . 'bundles/' .
      TEST_BUNDLE_UPPER . CLI_WARNING . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::CREATE_BUNDLE_MASK_NOTHING,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateBundleTask_BundleAlreadyExists_force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::CREATE_BUNDLE_MASK_FULL,
        self::CREATE_BUNDLE_NO_INTERACTIVE_MODE,
        self::CREATE_BUNDLE_FORCE
      ]
    );

    // testing
    $this->testAll();
  }
}
