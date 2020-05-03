<?php
namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

define('TEST_BUNDLE_UPPER', ucfirst(CreateBundleTaskTest::TEST_BUNDLE));
define('TEST_BUNDLE_PATH', BASE_PATH . 'bundles/' . TEST_BUNDLE_UPPER . '/');

/**
 * @runTestsInSeparateProcesses
 */
class CreateBundleTaskTest extends TestCase
{
  const TEST_TASK = 'createBundle',
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
  public function testCreateBundleTask_BundleAlreadyExists() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_YELLOW . 'The bundle ' . CLI_LIGHT_CYAN . 'bundles/' .
      TEST_BUNDLE_UPPER . CLI_YELLOW . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        '8',
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateBundleTask() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        '8',
        'false'
      ]
    );

    // testing
    $this->assertFileExists(TEST_BUNDLE_PATH);
  }
}
