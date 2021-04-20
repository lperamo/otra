<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

if (!defined('TEST_BUNDLE_UPPER'))
  define('TEST_BUNDLE_UPPER', ucfirst(CreateModuleTaskTest::TEST_BUNDLE));

if (!defined('TEST_BUNDLE_PATH'))
  define('TEST_BUNDLE_PATH', BUNDLES_PATH . TEST_BUNDLE_UPPER . '/');

if (!defined('TEST_MODULE_PATH'))
  define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateModuleTaskTest::TEST_MODULE . '/');

if (!defined('OTRA_LABEL_FALSE'))
  define('OTRA_LABEL_FALSE', 'false');

if (!defined('OTRA_BINARY_NAME'))
  define('OTRA_BINARY_NAME', 'otra.php');

/**
 * @runTestsInSeparateProcesses
 */
class CreateModuleTaskTest extends TestCase
{
  private const TEST_TASK = 'createModule';
  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test';

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
  public function testCreateModuleTask_BundleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_ERROR . 'The bundle ' . CLI_INFO_HIGHLIGHT . TEST_BUNDLE_UPPER . CLI_ERROR .
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
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_ModuleAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_MODULE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . 'bundles/' . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL);
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
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(TEST_BUNDLE_PATH))
      mkdir(TEST_BUNDLE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(TEST_MODULE_PATH);
  }
}
