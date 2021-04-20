<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

if (!defined('OTRA_BINARY_NAME'))
  define('OTRA_BINARY_NAME', 'otra.php');

define('OTRA_LABEL_BUNDLE', ' bundle.');
define('OTRA_LABEL_WE_USE_THE', 'We use the ');
define('OTRA_LABEL_FOR_THE_MODULE', ' for the module ');
define('OTRA_LABEL_A_MODEL_IN_THE_BUNDLE', 'A model in the bundle ');
define('OTRA_LABEL_THE_MODEL', 'The model ');
define('OTRA_LABEL_HAS_BEEN_CREATED_IN_THE_BUNDLE', ' has been created in the bundle ');

define(
  'OTRA_LABEL_YAML_SCHEMA_WARNING',
  CLI_WARNING . 'The YAML schema does not exist so we will create a model from the console parameters.' .
  END_COLOR . PHP_EOL
);
define(
  'OTRA_LABEL_NAME_MODEL_NOT_SPECIFIED_WE_USE_THE',
  CLI_WARNING . 'You did not specified the name of the model. We will import all the models.' .
  END_COLOR . PHP_EOL .
  OTRA_LABEL_WE_USE_THE . CLI_INFO_HIGHLIGHT . CreateModelTaskTest::BUNDLE_NAME . END_COLOR . OTRA_LABEL_BUNDLE. PHP_EOL
);
define('OTRA_LIBRARY_COPY_FILES_AND_FOLDERS', CORE_PATH . 'tools/copyFilesAndFolders.php');

if (!defined('TEST_BUNDLE_UPPER'))
  define('TEST_BUNDLE_UPPER', ucfirst(CreateModelTaskTest::BUNDLE_NAME));

if (!defined('TEST_BUNDLE_PATH'))
  define('TEST_BUNDLE_PATH', BASE_PATH . CreateModelTaskTest::BUNDLE_RELATIVE_PATH);

/**
 * @runTestsInSeparateProcesses
 */
class CreateModelTaskTest extends TestCase
{
  public const BUNDLE_NAME = 'test',
    BUNDLE_RELATIVE_PATH = 'bundles/' . TEST_BUNDLE_UPPER . '/';

  private const TASK_NAME = 'createModel',
    MODULE_NAME = 'test',
    INTERACTIVE = 'false',
    MODEL_NAME = 'testModel',
    MODEL_NAME_2 = 'testDB_table',
    MODEL_NAME_3 = 'testDB_table2',
    MODEL_NAME_4 = 'testDB_table3',
    FROM_NOTHING = 1,
    ONE_MODEL = 2,
    ALL_MODELS = 3,
    MODEL_LOCATION_BUNDLE = 0,
    MODEL_LOCATION_MODULE = 1,
    MODEL_PROPERTIES = 'rock, paper, scissors, lizard, spock',
    MODEL_SQL_TYPES = 'text,int,bool,date,float',
    SCHEMA_YML_FILE = 'schema.yml',
    BACKUP_YAML_SCHEMA = TEST_PATH . 'config/data/ymlBackup/' . self::SCHEMA_YML_FILE,
    MODULE_PATH = TEST_BUNDLE_PATH . CreateModelTaskTest::MODULE_NAME . '/',
    YAML_SCHEMA_RELATIVE_PATH_FROM_BUNDLE_PATH = 'config/data/yml/' . self::SCHEMA_YML_FILE,
    YAML_SCHEMA = TEST_BUNDLE_PATH . self::YAML_SCHEMA_RELATIVE_PATH_FROM_BUNDLE_PATH,
    OTRA_SUCCESS = CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL;

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function tearDown() : void
  {
    parent::tearDown();

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
   * @param int $modelLocation
   *
   * @return string
   */
  protected static function returnModelCreationFromNothingOutput(int $modelLocation = self::MODEL_LOCATION_BUNDLE) : string
  {
    return OTRA_LABEL_WE_USE_THE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_BUNDLE . PHP_EOL .
      'We will create one model from nothing.' . PHP_EOL .
      ($modelLocation === self::MODEL_LOCATION_BUNDLE
        ? 'A model for the bundle ' . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . ' ...'
        : OTRA_LABEL_A_MODEL_IN_THE_BUNDLE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_FOR_THE_MODULE .
        CLI_INFO_HIGHLIGHT . self::MODULE_NAME . END_COLOR . ' ...')
      . PHP_EOL .
      OTRA_LABEL_THE_MODEL . CLI_INFO_HIGHLIGHT . self::MODEL_NAME . END_COLOR . ' will be created from nothing...' . PHP_EOL .
      OTRA_LABEL_THE_MODEL . CLI_INFO_HIGHLIGHT . self::MODEL_NAME . END_COLOR . OTRA_LABEL_HAS_BEEN_CREATED_IN_THE_BUNDLE .
      CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . '.' . self::OTRA_SUCCESS;
  }

  /**
   * @param string $model
   *
   * @return string
   */
  protected static function modelHasBeenCreatedOutput(string $model) : string
  {
    return OTRA_LABEL_THE_MODEL . CLI_INFO_HIGHLIGHT . $model . END_COLOR . OTRA_LABEL_HAS_BEEN_CREATED_IN_THE_BUNDLE . CLI_INFO_HIGHLIGHT .
      self::BUNDLE_NAME . END_COLOR . '.' . self::OTRA_SUCCESS;
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_NoSchema_InBundle_FromNothing() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_YAML_SCHEMA_WARNING . self::returnModelCreationFromNothingOutput()
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::FROM_NOTHING,
        self::INTERACTIVE,
        self::MODEL_LOCATION_BUNDLE,
        self::MODULE_NAME,
        self::MODEL_NAME,
        self::MODEL_PROPERTIES,
        self::MODEL_SQL_TYPES
      ]
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_WithSchema_InBundle_FromNothing() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    require OTRA_LIBRARY_COPY_FILES_AND_FOLDERS;
    copyFileAndFolders([self::BACKUP_YAML_SCHEMA], [self::YAML_SCHEMA]);

    // assertions
    $this->expectOutputString(self::returnModelCreationFromNothingOutput());

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::FROM_NOTHING,
        self::INTERACTIVE,
        self::MODEL_LOCATION_BUNDLE,
        self::MODULE_NAME,
        self::MODEL_NAME,
        self::MODEL_PROPERTIES,
        self::MODEL_SQL_TYPES
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_NoSchema_InModule_FromNothing() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_YAML_SCHEMA_WARNING .
      self::returnModelCreationFromNothingOutput(self::MODEL_LOCATION_MODULE)
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::FROM_NOTHING,
        self::INTERACTIVE,
        self::MODEL_LOCATION_MODULE,
        self::MODULE_NAME,
        self::MODEL_NAME,
        self::MODEL_PROPERTIES,
        self::MODEL_SQL_TYPES
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_NoSchema_InModule_FromNothing_NoSqlTypes() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_YAML_SCHEMA_WARNING . CLI_ERROR . 'You did not specified the model properties types.' .
      END_COLOR . PHP_EOL
    );

    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::FROM_NOTHING,
        self::INTERACTIVE,
        self::MODEL_LOCATION_MODULE,
        self::MODULE_NAME,
        self::MODEL_NAME,
        self::MODEL_PROPERTIES
      ]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_NoSchema_NoModelName() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(TEST_BUNDLE_PATH, 0777, true);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_NAME_MODEL_NOT_SPECIFIED_WE_USE_THE .
      CLI_ERROR . 'The YAML schema ' . CLI_TABLE . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT . self::BUNDLE_RELATIVE_PATH .
      self::YAML_SCHEMA_RELATIVE_PATH_FROM_BUNDLE_PATH . CLI_ERROR .
      ' does not exist.' . END_COLOR . PHP_EOL
    );

    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::FROM_NOTHING,
        self::INTERACTIVE,
        self::MODEL_LOCATION_MODULE,
        self::MODULE_NAME
      ]
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_Task_InBundle_OneModel() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::MODULE_PATH, 0777, true);
    require OTRA_LIBRARY_COPY_FILES_AND_FOLDERS;
    copyFileAndFolders([self::BACKUP_YAML_SCHEMA], [self::YAML_SCHEMA]);

    // assertions
    $this->expectOutputString(OTRA_LABEL_WE_USE_THE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_BUNDLE .
      PHP_EOL .
      'We will create one model from ' . CLI_INFO_HIGHLIGHT . self::SCHEMA_YML_FILE . END_COLOR . '.' . PHP_EOL .
      OTRA_LABEL_THE_MODEL . CLI_INFO_HIGHLIGHT . self::MODEL_NAME_2 . END_COLOR . OTRA_LABEL_HAS_BEEN_CREATED_IN_THE_BUNDLE .
      CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . '.' . self::OTRA_SUCCESS
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::ONE_MODEL,
        self::INTERACTIVE,
        self::MODEL_LOCATION_BUNDLE,
        self::MODULE_NAME,
        self::MODEL_NAME_2
      ]
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCreateModel_NotInteractive_Task_InModule_OneModel() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::MODULE_PATH, 0777, true);
    require OTRA_LIBRARY_COPY_FILES_AND_FOLDERS;
    copyFileAndFolders([self::BACKUP_YAML_SCHEMA], [self::YAML_SCHEMA]);

    // assertions
    $this->expectOutputString(OTRA_LABEL_WE_USE_THE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_BUNDLE .
      PHP_EOL .
      'We will create one model from ' . CLI_INFO_HIGHLIGHT . self::SCHEMA_YML_FILE . END_COLOR . '.' . PHP_EOL .
      OTRA_LABEL_A_MODEL_IN_THE_BUNDLE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_FOR_THE_MODULE . CLI_INFO_HIGHLIGHT .
      self::MODULE_NAME . END_COLOR . ' ...' . PHP_EOL .
      OTRA_LABEL_THE_MODEL . CLI_INFO_HIGHLIGHT . self::MODEL_NAME_2 . END_COLOR . OTRA_LABEL_HAS_BEEN_CREATED_IN_THE_BUNDLE .
      CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . '.' . self::OTRA_SUCCESS
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::ONE_MODEL,
        self::INTERACTIVE,
        self::MODEL_LOCATION_MODULE,
        self::MODULE_NAME,
        self::MODEL_NAME_2
      ]
    );
  }

  /**
   * @throws OtraException
   */
  public function testCreateModel_NotInteractive_Task_InBundle_AllModels() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::MODULE_PATH, 0777, true);
    require OTRA_LIBRARY_COPY_FILES_AND_FOLDERS;
    copyFileAndFolders([self::BACKUP_YAML_SCHEMA], [self::YAML_SCHEMA]);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_NAME_MODEL_NOT_SPECIFIED_WE_USE_THE .
      'We will create all the models from ' . CLI_INFO_HIGHLIGHT . self::SCHEMA_YML_FILE . END_COLOR . '.' . PHP_EOL .
      'Creating all the models for the bundle ' . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . ' ...' . PHP_EOL .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_2) .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_3) .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_4)
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::ALL_MODELS,
        self::INTERACTIVE,
        self::MODEL_LOCATION_BUNDLE,
        self::MODULE_NAME
      ]
    );
  }

  /**
   * @throws OtraException
   */
  public function testCreateModel_NotInteractive_Task_InModule_AllModels() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::MODULE_PATH, 0777, true);
    require OTRA_LIBRARY_COPY_FILES_AND_FOLDERS;
    copyFileAndFolders([self::BACKUP_YAML_SCHEMA], [self::YAML_SCHEMA]);

    // assertions
    $this->expectOutputString(
      OTRA_LABEL_NAME_MODEL_NOT_SPECIFIED_WE_USE_THE .
      'We will create all the models from ' . CLI_INFO_HIGHLIGHT . self::SCHEMA_YML_FILE . END_COLOR . '.' . PHP_EOL .
      OTRA_LABEL_A_MODEL_IN_THE_BUNDLE . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . OTRA_LABEL_FOR_THE_MODULE . CLI_INFO_HIGHLIGHT .
      self::MODULE_NAME . END_COLOR . ' ...' . PHP_EOL .
      'Creating all the models for the bundle ' . CLI_INFO_HIGHLIGHT . self::BUNDLE_NAME . END_COLOR . ' in the module ' .
      CLI_INFO_HIGHLIGHT . self::MODULE_NAME . END_COLOR . ' ...' . PHP_EOL .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_2) .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_3) .
      self::modelHasBeenCreatedOutput(self::MODEL_NAME_4)
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_NAME,
      [
        OTRA_BINARY_NAME,
        self::TASK_NAME,
        self::BUNDLE_NAME,
        self::ALL_MODELS,
        self::INTERACTIVE,
        self::MODEL_LOCATION_MODULE,
        self::MODULE_NAME
      ]
    );
  }
}
