<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

define('OTRA_BUNDLES_FOLDER_NAME', 'bundles/');
define('OTRA_LABEL_DOES_NOT_EXIST', ' does not exist.');
define('OTRA_LABEL_FALSE', 'false');
define('OTRA_BINARY_NAME', 'otra.php');

define('TEST_BUNDLE_UPPER', ucfirst(CreateActionTaskTest::TEST_BUNDLE));
define('TEST_BUNDLES_MAIN_FOLDER', BASE_PATH . OTRA_BUNDLES_FOLDER_NAME);
define('TEST_BUNDLE_PATH', TEST_BUNDLES_MAIN_FOLDER . TEST_BUNDLE_UPPER . '/');
define('TEST_MODULE_PATH', TEST_BUNDLE_PATH . CreateActionTaskTest::TEST_MODULE . '/');
define('TEST_CONTROLLER_PATH', TEST_MODULE_PATH . 'controllers/' . CreateActionTaskTest::TEST_CONTROLLER . '/');
define('TEST_ACTION_FULL', ucfirst(CreateActionTaskTest::TEST_ACTION) . 'Action.php');
define('TEST_ACTION_PATH', TEST_CONTROLLER_PATH . TEST_ACTION_FULL);
define('TEST_VIEWS_PATH', TEST_MODULE_PATH . 'views/');
define('TEST_VIEWS_SUBFOLDER_PATH', TEST_VIEWS_PATH . CreateActionTaskTest::TEST_CONTROLLER . '/');
define('TEST_CLASS_MAP_PATH', BASE_PATH . 'cache/php/tasksClassMap.php');

/**
 * @runTestsInSeparateProcesses
 */
class CreateActionTaskTest extends TestCase
{
  private const TEST_TASK = 'createAction',
    TEST_BUNDLES_CONFIG_PATH = BASE_PATH . 'bundles/config/',
    TEST_BUNDLES_CONFIG_FILE_PATH = self::TEST_BUNDLES_CONFIG_PATH . 'Routes.php',
    TEST_BUNDLE_CONFIG_PATH = TEST_BUNDLE_PATH . 'config/',
    TEST_BUNDLE_ROUTES_PATH = self::TEST_BUNDLE_CONFIG_PATH . 'Routes.php';

  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test',
    TEST_ACTION = 'test';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'prod';
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (OTRA_PROJECT === false && file_exists(TEST_BUNDLE_PATH))
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      $delTree(TEST_BUNDLE_PATH);

      if (file_exists(self::TEST_BUNDLES_CONFIG_FILE_PATH))
        unlink(self::TEST_BUNDLES_CONFIG_FILE_PATH);

      if (file_exists(self::TEST_BUNDLES_CONFIG_PATH))
        rmdir(self::TEST_BUNDLES_CONFIG_PATH);

      rmdir(TEST_BUNDLES_MAIN_FOLDER);
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_BundleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_RED . 'The bundle ' . CLI_LIGHT_CYAN . TEST_BUNDLE_UPPER . CLI_RED .
      OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);

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
        self::TEST_ACTION,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;

    if (!file_exists(TEST_BUNDLE_PATH))
      mkdir(TEST_BUNDLE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_RED . 'The module ' . CLI_LIGHT_CYAN . OTRA_BUNDLES_FOLDER_NAME . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . CLI_RED . OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);
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
        self::TEST_ACTION,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ControllerDoNotExist() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;
    mkdir(TEST_MODULE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_RED . 'The controller ' . CLI_LIGHT_CYAN . OTRA_BUNDLES_FOLDER_NAME .
      TEST_BUNDLE_UPPER . '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_RED .
      OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);
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
        self::TEST_ACTION,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ActionAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;
    mkdir(TEST_CONTROLLER_PATH, 0777, true);
    touch(TEST_ACTION_PATH);

    // testing
    $this->expectOutputString(CLI_RED . 'The action ' . CLI_LIGHT_CYAN . OTRA_BUNDLES_FOLDER_NAME . TEST_BUNDLE_UPPER .
      '/' . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . '/' . TEST_ACTION_FULL . CLI_RED .
      ' already exists.' . END_COLOR . PHP_EOL);
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
        self::TEST_ACTION,
        OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask() : void
  {
    // context
    $tasksClassMap = require TEST_CLASS_MAP_PATH;
    mkdir(TEST_CONTROLLER_PATH, 0777, true);

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
        self::TEST_ACTION,
        OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(TEST_ACTION_PATH);
    self::assertFileEquals(TEST_PATH . 'examples/createAction/Action.php', TEST_ACTION_PATH);
    self::assertFileExists(TEST_VIEWS_PATH);
    self::assertFileExists(TEST_VIEWS_SUBFOLDER_PATH);
    self::assertFileExists(TEST_VIEWS_SUBFOLDER_PATH . self::TEST_ACTION . '.phtml');

    self::assertFileExists(self::TEST_BUNDLES_CONFIG_FILE_PATH);

    self::assertFileExists(self::TEST_BUNDLE_ROUTES_PATH);
    self::assertFileEquals(
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
