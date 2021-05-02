<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tools\delTree;

if (!defined('src\console\architecture\TEST_BUNDLE_UPPER'))
  define('src\console\architecture\TEST_BUNDLE_UPPER', ucfirst(CreateModuleTaskTest::TEST_BUNDLE));

/**
 * @runTestsInSeparateProcesses
 */
class CreateModuleTaskTest extends TestCase
{
  private const 
    TEST_TASK = 'createModule',
    TEST_BUNDLE_PATH = BUNDLES_PATH . TEST_BUNDLE_UPPER . DIR_SEPARATOR,
    TEST_MODULE_PATH = self::TEST_BUNDLE_PATH . CreateModuleTaskTest::TEST_MODULE . DIR_SEPARATOR,
    OTRA_LABEL_FALSE = 'false',
    OTRA_BINARY_NAME = 'otra.php';

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
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::OTRA_LABEL_FALSE
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
    mkdir(self::TEST_MODULE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . 'bundles/' . TEST_BUNDLE_UPPER .
      DIR_SEPARATOR . self::TEST_MODULE . CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL);
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
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateModuleTask() : void
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
        self::OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_MODULE_PATH);
  }
}
