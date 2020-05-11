<?php
namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

define('TEST_BUNDLE_UPPER', ucfirst(CreateControllerTaskTest::TEST_BUNDLE));
define('TEST_BUNDLE_PATH', BASE_PATH . 'bundles/' . TEST_BUNDLE_UPPER . '/');
define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateControllerTaskTest::TEST_MODULE . '/');
define('TEST_CONTROLLER_PATH', TEST_MODULE_PATH . 'controllers/' . CreateControllerTaskTest::TEST_CONTROLLER . '/');

/**
 * @runTestsInSeparateProcesses
 */
class CreateControllerTaskTest extends TestCase
{
  const TEST_TASK = 'createController',
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test';

  protected function tearDown(): void
  {
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
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_RED . 'The bundle ' . CLI_LIGHT_CYAN . TEST_BUNDLE_UPPER . CLI_RED .
      ' does not exist.' . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_RED . 'The module ' . CLI_LIGHT_CYAN . 'bundles/' . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . CLI_RED . ' does not exist.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ControllerAlreadyExists() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_CONTROLLER_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_RED . 'The controller ' . CLI_LIGHT_CYAN . 'bundles/' . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_RED . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_MODULE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        'false'
      ]
    );

    // testing
    $this->assertFileExists(TEST_CONTROLLER_PATH);
  }
}
