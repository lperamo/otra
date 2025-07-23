<?php
declare(strict_types=1);

namespace src\console\deployment\buildDev;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BUNDLES_PATH, CORE_PATH, TEST_PATH};
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_SUCCESS, CLI_WARNING, CLI_INFO_HIGHLIGHT, CLI_TABLE, END_COLOR, SUCCESS};
use function otra\tools\delTree;

/**
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class BuildDevTaskTest extends TestCase
{
  private const string
    FAKE_ROUTE_NAME = 'fake',
    GCC_FALSE = 'false',
    GOOD_ROUTE = 'test',
    SCOPE_DEFAULT = '0',
    TASK_NAME = 'buildDev',
    OTRA_BINARY_NAME = 'otra.php',
    TEST_CONFIG_PATH = BUNDLES_PATH . 'config/',
    TEST_ROUTES_PATH = self::TEST_CONFIG_PATH . 'Routes.php',
    VERBOSE_NONE = '0',
    MASK_NONE = '0';

  private array $tasksClassMap = [];

  protected function setUp(): void
  {
    $this->tasksClassMap = require TASK_CLASS_MAP_PATH;
    require TEST_PATH . 'config/AllConfig.php';
    parent::setUp();
  }

  private function cleanUp(): void
  {
    if (file_exists(BUNDLES_PATH))
    {
      require_once CORE_PATH . '/tools/deleteTree.php';
      delTree(BUNDLES_PATH);
    }
  }

  /**
   * @throws OtraException
   */
  public function testBuildDev_NoRoutes(): void
  {
    try
    {
      // assertions
      $this->expectException(OtraException::class);
      $this->expectOutputString(
        CLI_ERROR . 'Either you do not have any routes or you have to update your configuration with ' .
        CLI_INFO_HIGHLIGHT . 'otra updateConf' . CLI_ERROR . '.' . PHP_EOL
      );

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          self::VERBOSE_NONE,
          self::MASK_NONE
        ]
      );
    } finally
    {
      $this->cleanUp();
    }
  }

  /**
   * @throws OtraException
   */
  public function testBuildDev_RouteDoesNotExist(): void
  {
    // context
    mkdir(self::TEST_CONFIG_PATH, 0777, true);
    file_put_contents(self::TEST_ROUTES_PATH, '<?php return [];');

    try
    {
      // assertions
      $this->expectException(OtraException::class);
      $this->expectOutputString(
        CLI_ERROR . 'There is no route ' . CLI_INFO_HIGHLIGHT . self::FAKE_ROUTE_NAME . CLI_ERROR . '.' . PHP_EOL
      );

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          self::VERBOSE_NONE,
          self::MASK_NONE,
          self::GCC_FALSE,
          self::SCOPE_DEFAULT,
          self::FAKE_ROUTE_NAME
        ]
      );
    } finally
    {
      $this->cleanUp();
    }
  }

  /**
   * @throws OtraException
   */
  public function testBuildDev_NoFilesToProcess(): void
  {
    try
    {
      // context
      mkdir(self::TEST_CONFIG_PATH, 0777, true);
      file_put_contents(self::TEST_ROUTES_PATH, '<?php return [];');

      // assertions
      $this->expectOutputString(CLI_WARNING . 'No files to process.' . END_COLOR . PHP_EOL);

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          self::VERBOSE_NONE,
          self::MASK_NONE
        ]
      );
    } finally
    {
      $this->cleanUp();
    }
  }

  /**
   * @medium
   * @throws OtraException
   */
  public function testBuildDev_FilesProcessed(): void
  {
    try
    {
      // context
      $resourceFile = 'test.scss';
      $resourcePathFolder = BUNDLES_PATH . 'resources/scss/';
      $baseFolder = 'bundles/resources/';
      $resourcePathRelativeFolder = $baseFolder . 'scss/';
      $resourcePath = $resourcePathRelativeFolder . $resourceFile;
      $resourceFullPath = $resourcePathFolder . $resourceFile;
      mkdir(self::TEST_CONFIG_PATH, 0777, true);
      mkdir($resourcePathFolder, 0777, true);
      file_put_contents($resourceFullPath, '');
      file_put_contents(
        self::TEST_ROUTES_PATH,
        '<?php return [
        \'test\'=> [
          \'chunks\' => [\'/profiler/requests\', \'testBundle\', \'otra\', \'profiler\', \'requestsAction\'],
          \'resources\' => [
          \'app_css\' => [\'test\']
        ]
        ]];'
      );

      // assertions
      $this->expectOutputString(
        'Class mapping finished' . SUCCESS .
        'Launching routes update...' . PHP_EOL . CLI_TABLE . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT .
        'bundles/config/Routes.php' . CLI_BASE . ' updated' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
        'SCSS file ' . CLI_INFO . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT . $resourcePath . END_COLOR . ' have generated ' . 
        CLI_INFO . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT . $baseFolder . 'css/test/test.css' . END_COLOR . '.' . PHP_EOL
      );

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          true,
          15,
          self::GCC_FALSE,
          self::SCOPE_DEFAULT,
        ]
      );
    } finally
    {
//      $this->cleanUp();
    }
  }

  /**
   * @small 
   * @throws OtraException
   */
  public function testBuildDev_NoFilesProcessed(): void
  {
    try 
    {
      // context
      mkdir(self::TEST_CONFIG_PATH, 0777, true);
      file_put_contents(self::TEST_ROUTES_PATH, '<?php return [];');

      // assertions
      $this->expectOutputString(CLI_WARNING . 'No files to process.' . END_COLOR . PHP_EOL);

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          self::VERBOSE_NONE,
          self::MASK_NONE,
          self::GCC_FALSE,
          self::SCOPE_DEFAULT,
        ]
      );
    } finally
    {
      $this->cleanUp();
    }
  }

  /**
   * @small
   * @throws OtraException
   */
  public function testBuildDev_NoFilesProcessedForSpecificRoute(): void
  {
    try
    {
      // context
      mkdir(self::TEST_CONFIG_PATH, 0777, true);
      file_put_contents(
        self::TEST_ROUTES_PATH, 
        '<?php return [
          \'test\'=> [
            \'chunks\' => [\'/profiler/requests\', \'\', \'otra\', \'profiler\', \'requestsAction\'],
            \'resources\' => [
            \'core_css\' => []
          ]
        ]];');

      // assertions
      $this->expectOutputString(
        CLI_WARNING . 'No files to process for the route ' . CLI_INFO_HIGHLIGHT . 'test' . CLI_WARNING . '.' . 
        END_COLOR . PHP_EOL
      );

      // launching
      TasksManager::execute(
        $this->tasksClassMap,
        self::TASK_NAME,
        [
          self::OTRA_BINARY_NAME,
          self::TASK_NAME,
          self::VERBOSE_NONE,
          self::MASK_NONE,
          self::GCC_FALSE,
          self::SCOPE_DEFAULT,
          self::GOOD_ROUTE
        ]
      );
    } finally
    {
      $this->cleanUp();
    }
  }
}
