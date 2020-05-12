<?php
namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

define('TEST_BUNDLE_UPPER', ucfirst(CreateActionTaskTest::TEST_BUNDLE));
define('TEST_BUNDLE_PATH', BASE_PATH . 'bundles/' . TEST_BUNDLE_UPPER . '/');
define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateActionTaskTest::TEST_MODULE . '/');
define('TEST_CONTROLLER_PATH', TEST_MODULE_PATH . 'controllers/' . CreateActionTaskTest::TEST_CONTROLLER . '/');
define('TEST_ACTION_FULL', ucfirst(CreateActionTaskTest::TEST_ACTION) . 'Action.php');
define('TEST_ACTION_PATH', TEST_CONTROLLER_PATH . TEST_ACTION_FULL);
define('TEST_VIEWS_PATH', TEST_MODULE_PATH . 'views/');
define('TEST_VIEWS_SUBFOLDER_PATH', TEST_VIEWS_PATH . CreateActionTaskTest::TEST_CONTROLLER . '/');

/**
 * @runTestsInSeparateProcesses
 */
class CreateActionTaskTest extends TestCase
{
  const TEST_TASK = 'createAction',
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test',
    TEST_ACTION = 'test',
    TEST_BUNDLES_CONFIG_PATH = BASE_PATH . 'bundles/config/',
    TEST_BUNDLES_CONFIG_FILE_PATH = self::TEST_BUNDLES_CONFIG_PATH . 'Routes.php',
    TEST_BUNDLE_CONFIG_PATH = TEST_BUNDLE_PATH . 'config/',
    TEST_BUNDLE_ROUTES_PATH = self::TEST_BUNDLE_CONFIG_PATH . 'Routes.php';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

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
  public function testCreateActionTask_BundleDoNotExist() : void
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
        self::TEST_ACTION,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // testing
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
        self::TEST_ACTION,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ControllerDoNotExist() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_MODULE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_RED . 'The controller ' . CLI_LIGHT_CYAN . 'bundles/' .
      TEST_BUNDLE_UPPER . '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_RED .
      ' does not exist.' . END_COLOR . PHP_EOL);
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
        self::TEST_ACTION,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ActionAlreadyExists() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_CONTROLLER_PATH, 0777, true);
    touch(TEST_ACTION_PATH);

    // testing
    $this->expectOutputString(CLI_RED . 'The action ' . CLI_LIGHT_CYAN . 'bundles/' . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . '/' . TEST_ACTION_FULL . CLI_RED .
      ' already exists.' . END_COLOR . PHP_EOL);
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
        self::TEST_ACTION,
        'false'
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(TEST_CONTROLLER_PATH, 0777, true);

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
        self::TEST_ACTION,
        'false'
      ]
    );

    // testing
    $this->assertFileExists(TEST_ACTION_PATH);
    $this->assertFileEquals(TEST_PATH . 'examples/createAction/Action.php', TEST_ACTION_PATH);
    $this->assertFileExists(TEST_VIEWS_PATH);
    $this->assertFileExists(TEST_VIEWS_SUBFOLDER_PATH);
    $this->assertFileExists(TEST_VIEWS_SUBFOLDER_PATH . self::TEST_ACTION . '.phtml');

    $this->assertFileExists(self::TEST_BUNDLES_CONFIG_FILE_PATH);

    $this->assertFileExists(self::TEST_BUNDLE_ROUTES_PATH);
    $this->assertFileEquals(
      TEST_PATH . 'examples/createAction/Routes.php',
      self::TEST_BUNDLE_ROUTES_PATH
    );

    // cleaning
    if (OTRA_PROJECT === false)
    {
      unlink(self::TEST_BUNDLES_CONFIG_FILE_PATH);
      rmdir(self::TEST_BUNDLES_CONFIG_PATH);

      unlink(self::TEST_BUNDLE_ROUTES_PATH);
      rmdir(self::TEST_BUNDLE_CONFIG_PATH);
    }
  }
}