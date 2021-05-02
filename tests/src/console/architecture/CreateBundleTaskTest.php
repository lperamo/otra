<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use phpunit\framework\TestCase;
use const \otra\tests\BUNDLES_PATH;
use function otra\tools\delTree;

if (!defined('TEST_BUNDLE_UPPER'))
{
  define('TEST_BUNDLE_UPPER', ucfirst(CreateBundleTaskTest::TEST_BUNDLE));
  define('TEST_BUNDLE_PATH', BUNDLES_PATH . TEST_BUNDLE_UPPER . '/');
}

/**
 * @runTestsInSeparateProcesses
 */
class CreateBundleTaskTest extends TestCase
{
  public const TEST_BUNDLE = 'test';
  private const TEST_TASK = 'createBundle';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function tearDown(): void
  {
    parent::tearDown();
    // cleaning
    if (OTRA_PROJECT === false && file_exists(TEST_BUNDLE_PATH))
    {
      delTree(TEST_BUNDLE_PATH);
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateBundleTask_BundleAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(CLI_WARNING . 'The bundle ' . CLI_INFO_HIGHLIGHT . 'bundles/' .
      TEST_BUNDLE_UPPER . CLI_WARNING . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        '0',
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
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        'otra.php',
        self::TEST_TASK,
        self::TEST_BUNDLE,
        '15',
        'false'
      ]
    );

    // testing
    self::assertFileExists(TEST_BUNDLE_PATH);
    self::assertFileExists(TEST_BUNDLE_PATH . 'config/');
    self::assertFileExists(TEST_BUNDLE_PATH . 'models/');
    self::assertFileExists(TEST_BUNDLE_PATH . 'resources/');
    self::assertFileExists(TEST_BUNDLE_PATH . 'views/');
  }
}
