<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

if (!defined('OTRA_LABEL_FALSE'))
  define('OTRA_LABEL_FALSE', 'false');

define('OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME', 'bundles/');

if (!defined('OTRA_BINARY_NAME'))
  define('OTRA_BINARY_NAME', 'otra.php');

if (!defined('TEST_BUNDLE_UPPER'))
  define('TEST_BUNDLE_UPPER', ucfirst(CreateControllerTaskTest::TEST_BUNDLE));

if (!defined('TEST_BUNDLE_PATH'))
  define('TEST_BUNDLE_PATH', BASE_PATH . OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME . TEST_BUNDLE_UPPER . '/');

if (!defined('TEST_MODULE_PATH'))
  define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateControllerTaskTest::TEST_MODULE . '/');

if (!defined('TEST_CONTROLLER_PATH'))
  define('TEST_CONTROLLER_PATH', TEST_MODULE_PATH . 'controllers/' . CreateControllerTaskTest::TEST_CONTROLLER . '/');

if (!defined('TEST_CLASS_MAP_PATH'))
  define('TEST_CLASS_MAP_PATH', BASE_PATH . 'cache/php/tasksClassMap.php');

/**
 * @runTestsInSeparateProcesses
 */
class CreateControllerTaskTest extends TestCase
{
  private const TEST_TASK = 'createController';
  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function tearDown(): void
  {
    parent::tearDown();
    // cleaning
    if (OTRA_PROJECT === false && file_exists(TEST_BUNDLE_PATH))
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      $delTree(TEST_BUNDLE_PATH);
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_BundleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_RED . 'The bundle ' . CLI_LIGHT_CYAN . TEST_BUNDLE_UPPER . CLI_RED .
      ' does not exist.' . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_RED . 'The module ' . CLI_LIGHT_CYAN . OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . CLI_RED . ' does not exist.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ControllerAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;
    mkdir(TEST_CONTROLLER_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_RED . 'The controller ' . CLI_LIGHT_CYAN . OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_RED . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;

    if (!file_exists(TEST_MODULE_PATH))
      mkdir(TEST_MODULE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(TEST_CONTROLLER_PATH);
  }
}
