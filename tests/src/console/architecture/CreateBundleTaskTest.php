<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tools\delTree;

if (!defined('src\console\architecture\TEST_BUNDLE_UPPER'))
  define('src\console\architecture\TEST_BUNDLE_UPPER', ucfirst(CreateBundleTaskTest::TEST_BUNDLE));

/**
 * @runTestsInSeparateProcesses
 */
class CreateBundleTaskTest extends TestCase
{
  public const TEST_BUNDLE = 'test';
  private const 
    TEST_TASK = 'createBundle',
    TEST_BUNDLE_PATH = BUNDLES_PATH . TEST_BUNDLE_UPPER . DIR_SEPARATOR;
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
  public function testCreateBundleTask_BundleAlreadyExists() : void
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
   * @throws OtraException
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
    self::assertFileExists(self::TEST_BUNDLE_PATH);
    self::assertFileExists(self::TEST_BUNDLE_PATH . 'config/');
    self::assertFileExists(self::TEST_BUNDLE_PATH . 'models/');
    self::assertFileExists(self::TEST_BUNDLE_PATH . 'resources/');
    self::assertFileExists(self::TEST_BUNDLE_PATH . 'views/');
  }
}
