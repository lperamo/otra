<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tools\delTree;

if (!defined('src\console\architecture\TEST_BUNDLE_UPPER'))
  define('src\console\architecture\TEST_BUNDLE_UPPER', ucfirst(CreateControllerTaskTest::TEST_BUNDLE_NAME));

/**
 * @runTestsInSeparateProcesses
 */
class CreateControllerTaskTest extends TestCase
{
  private const
    TEST_TASK = 'createController',
    TEST_BUNDLE_PATH = BUNDLES_PATH . TEST_BUNDLE_UPPER . DIR_SEPARATOR,
    TEST_MODULE_PATH = self::TEST_BUNDLE_PATH . CreateControllerTaskTest::TEST_MODULE_NAME . DIR_SEPARATOR,
    TEST_CONTROLLER_PATH = self::TEST_MODULE_PATH . 'controllers/' . CreateControllerTaskTest::TEST_CONTROLLER_NAME .
      DIR_SEPARATOR,
    CREATE_CONTROLLER_NO_INTERACTIVE_MODE = 'false',
    OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME = 'bundles/',
    OTRA_BINARY_NAME = 'otra.php',
    CREATE_BUNDLE_FORCE = 'true';
  
  public const
    TEST_BUNDLE_NAME = 'test',
    TEST_MODULE_NAME = 'test',
    TEST_CONTROLLER_NAME = 'test';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function tearDown(): void
  {
    parent::tearDown();
    // cleaning
    if (!OTRA_PROJECT && file_exists(self::TEST_BUNDLE_PATH))
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      delTree(self::TEST_BUNDLE_PATH);
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_NoBundlesFolder() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // testing exceptions
    self::expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    self::expectOutputString(
      CLI_ERROR . 'There is no ' . CLI_INFO_HIGHLIGHT . 'bundles' . CLI_ERROR .
      ' folder to put bundles! Please create this folder or launch ' . CLI_INFO_HIGHLIGHT . 'otra init' . CLI_ERROR .
      ' to solve it.' . END_COLOR . PHP_EOL
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_BundleDoNotExist_noForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(BUNDLES_PATH, 0777, true);

    // testing exceptions
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(CLI_ERROR . 'The bundle ' . CLI_INFO_HIGHLIGHT . TEST_BUNDLE_UPPER .
      CLI_ERROR . ' does not exist.' . END_COLOR . PHP_EOL);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateControllerTask_BundleDoNotExist_force() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(BUNDLES_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE,
        self::CREATE_BUNDLE_FORCE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CONTROLLER_PATH);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ModuleDoNotExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_BUNDLE_PATH, 0777, true);

    // testing exceptions
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(CLI_ERROR . 'The module ' . CLI_INFO_HIGHLIGHT .
      substr(self::TEST_BUNDLE_PATH, strlen(BASE_PATH)) . self::TEST_MODULE_NAME . CLI_ERROR .
      ' does not exist.' . END_COLOR . PHP_EOL);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateControllerTask_ControllerAlreadyExists() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::TEST_CONTROLLER_PATH, 0777, true);

    // testing exceptions
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    $this->expectOutputString(
      CLI_ERROR . 'The controller ' . CLI_INFO_HIGHLIGHT . self::OTRA_LABEL_BUNDLES_MAIN_FOLDER_NAME .
      TEST_BUNDLE_UPPER . DIR_SEPARATOR . self::TEST_MODULE_NAME . '/controllers/' . self::TEST_CONTROLLER_NAME .
      CLI_ERROR . ' already exists.' . END_COLOR . PHP_EOL
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateControllerTask_NoForce() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    if (!file_exists(self::TEST_MODULE_PATH))
      mkdir(self::TEST_MODULE_PATH, 0777, true);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TEST_TASK,
      [
        self::OTRA_BINARY_NAME,
        self::TEST_TASK,
        self::TEST_BUNDLE_NAME,
        self::TEST_MODULE_NAME,
        self::TEST_CONTROLLER_NAME,
        self::CREATE_CONTROLLER_NO_INTERACTIVE_MODE
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CONTROLLER_PATH);
  }
}
