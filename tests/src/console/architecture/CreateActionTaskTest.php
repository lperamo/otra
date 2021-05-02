<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT, PROD, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tools\delTree;

define('src\console\architecture\TEST_BUNDLE_UPPER', ucfirst(CreateActionTaskTest::TEST_BUNDLE));
define('src\console\architecture\TEST_ACTION_FULL', ucfirst(CreateActionTaskTest::TEST_ACTION) . 'Action.php');

/**
 * @runTestsInSeparateProcesses
 */
class CreateActionTaskTest extends TestCase
{
  private const TEST_TASK = 'createAction',
    TEST_BUNDLES_CONFIG_PATH = BUNDLES_PATH . 'config/',
    TEST_BUNDLES_CONFIG_FILE_PATH = self::TEST_BUNDLES_CONFIG_PATH . 'Routes.php',
    TEST_BUNDLE_CONFIG_PATH = self::TEST_BUNDLE_PATH . 'config/',
    TEST_BUNDLE_ROUTES_PATH = self::TEST_BUNDLE_CONFIG_PATH . 'Routes.php',
    OTRA_BUNDLES_FOLDER_NAME = 'bundles/',
    OTRA_LABEL_DOES_NOT_EXIST = ' does not exist.',
    OTRA_LABEL_FALSE = 'false',
    OTRA_BINARY_NAME = 'otra.php',

    TEST_BUNDLES_MAIN_FOLDER = BASE_PATH . self::OTRA_BUNDLES_FOLDER_NAME,
    TEST_BUNDLE_PATH = self::TEST_BUNDLES_MAIN_FOLDER . TEST_BUNDLE_UPPER . DIR_SEPARATOR,
    TEST_MODULE_PATH = self::TEST_BUNDLE_PATH . CreateActionTaskTest::TEST_MODULE . DIR_SEPARATOR,
    TEST_CONTROLLER_PATH = self::TEST_MODULE_PATH . 'controllers/' . CreateActionTaskTest::TEST_CONTROLLER . DIR_SEPARATOR,
    TEST_ACTION_PATH = self::TEST_CONTROLLER_PATH . TEST_ACTION_FULL,
    TEST_VIEWS_PATH = self::TEST_MODULE_PATH . 'views/',
    TEST_VIEWS_SUBFOLDER_PATH = self::TEST_VIEWS_PATH . CreateActionTaskTest::TEST_CONTROLLER . DIR_SEPARATOR;

  public const
    TEST_BUNDLE = 'test',
    TEST_MODULE = 'test',
    TEST_CONTROLLER = 'test',
    TEST_ACTION = 'test';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (!OTRA_PROJECT && file_exists(self::TEST_BUNDLE_PATH))
    {
      require CORE_PATH . 'tools/deleteTree.php';
      delTree(self::TEST_BUNDLE_PATH);

      if (file_exists(self::TEST_BUNDLES_CONFIG_FILE_PATH))
        unlink(self::TEST_BUNDLES_CONFIG_FILE_PATH);

      if (file_exists(self::TEST_BUNDLES_CONFIG_PATH))
        rmdir(self::TEST_BUNDLES_CONFIG_PATH);

      rmdir(self::TEST_BUNDLES_MAIN_FOLDER);
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_BundleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // assertions
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_ERROR . 'The bundle ' . CLI_INFO_HIGHLIGHT . TEST_BUNDLE_UPPER . CLI_ERROR .
      self::OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::TEST_ACTION,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(self::TEST_BUNDLE_PATH))
      mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT . self::OTRA_BUNDLES_FOLDER_NAME . TEST_BUNDLE_UPPER .
      DIR_SEPARATOR . self::TEST_MODULE . CLI_ERROR . self::OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);
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
        self::TEST_CONTROLLER,
        self::TEST_ACTION,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ControllerDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_MODULE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_ERROR . 'The controller ' . CLI_INFO_HIGHLIGHT . self::OTRA_BUNDLES_FOLDER_NAME .
      TEST_BUNDLE_UPPER . DIR_SEPARATOR . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . CLI_ERROR .
      self::OTRA_LABEL_DOES_NOT_EXIST . END_COLOR . PHP_EOL);
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
        self::TEST_CONTROLLER,
        self::TEST_ACTION,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateActionTask_ActionAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_CONTROLLER_PATH, 0777, true);
    touch(self::TEST_ACTION_PATH);

    // testing
    $this->expectOutputString(CLI_ERROR . 'The action ' . CLI_INFO_HIGHLIGHT . self::OTRA_BUNDLES_FOLDER_NAME . TEST_BUNDLE_UPPER .
      DIR_SEPARATOR . self::TEST_MODULE . '/controllers/' . self::TEST_CONTROLLER . DIR_SEPARATOR . TEST_ACTION_FULL . CLI_ERROR .
      ' already exists.' . END_COLOR . PHP_EOL);
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
        self::TEST_CONTROLLER,
        self::TEST_ACTION,
        self::OTRA_LABEL_FALSE
      ]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateActionTask() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_CONTROLLER_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::TEST_MODULE,
        self::TEST_CONTROLLER,
        self::TEST_ACTION,
        self::OTRA_LABEL_FALSE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_ACTION_PATH);
    self::assertFileEquals(
      TEST_PATH . 'examples/createAction/Action.php',
      self::TEST_ACTION_PATH,
      'Testing action generated.'
    );
    self::assertFileExists(self::TEST_VIEWS_PATH);
    self::assertFileExists(self::TEST_VIEWS_SUBFOLDER_PATH);
    self::assertFileExists(self::TEST_VIEWS_SUBFOLDER_PATH . self::TEST_ACTION . '.phtml');

    self::assertFileExists(self::TEST_BUNDLES_CONFIG_FILE_PATH);

    self::assertFileExists(self::TEST_BUNDLE_ROUTES_PATH);
    self::assertFileEquals(
      TEST_PATH . 'examples/createAction/Routes.php',
      self::TEST_BUNDLE_ROUTES_PATH,
      'Testing routes generated.'
    );

    // cleaning
    if (!OTRA_PROJECT)
    {
      unlink(self::TEST_BUNDLES_CONFIG_FILE_PATH);
      rmdir(self::TEST_BUNDLES_CONFIG_PATH);

      unlink(self::TEST_BUNDLE_ROUTES_PATH);
      rmdir(self::TEST_BUNDLE_CONFIG_PATH);
    }
  }
}
