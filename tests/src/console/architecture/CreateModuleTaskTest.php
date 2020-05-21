<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

if (!defined('TEST_BUNDLE_UPPER'))
  define('TEST_BUNDLE_UPPER', ucfirst(CreateModuleTaskTest::TEST_BUNDLE));

if (!defined('TEST_BUNDLE_PATH'))
  define('TEST_BUNDLE_PATH', BASE_PATH . 'bundles/' . TEST_BUNDLE_UPPER . '/');

if (!defined('TEST_MODULE_PATH'))
  define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateModuleTaskTest::TEST_MODULE . '/');

/**
 * @runTestsInSeparateProcesses
 */
class CreateModuleTaskTest extends TestCase
{
  const TEST_TASK = 'createModule',
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test';

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
  public function testCreateModuleTask_BundleDoNotExist() : void
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
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask_ModuleAlreadyExists() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_MODULE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_RED . 'The module ' . CLI_LIGHT_CYAN . 'bundles/' . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . CLI_RED . ' already exists.' . END_COLOR . PHP_EOL);
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
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModuleTask() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        'false'
      ]
    );

    // testing
    $this->assertFileExists(TEST_MODULE_PATH);
  }
}
