<?php
declare(strict_types=1);

namespace src\console\deployment\buildDev;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use Throwable;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CORE_PATH, TEST_PATH};
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_SUCCESS, CLI_WARNING, CLI_INFO_HIGHLIGHT, CLI_TABLE, END_COLOR, SUCCESS};
use function otra\tools\delTree;

/**
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class BuildDevTaskTest extends TestCase
{
  private const string
    TASK_NAME = 'buildDev',
    OTRA_BINARY_NAME = 'otra.php',
    TEST_CONFIG_PATH = BUNDLES_PATH . 'config/',
    TEST_LOG_PATH = BASE_PATH . 'logs/dev/phpunit.txt',
    TEST_ROUTES_PATH = self::TEST_CONFIG_PATH . 'Routes.php';

  private const string
    VERBOSE_NONE = '0',
    SCOPE_NONE = '0';
  
  private array $tasksClassMap = [];

  protected function setUp(): void
  {
    $this->tasksClassMap = require TASK_CLASS_MAP_PATH;
    require TEST_PATH . 'config/AllConfig.php';
    parent::setUp();
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    $this->cleanUp();
  }

  protected function onNotSuccessfulTest(Throwable $t): never
  {
    try
    {
      $this->cleanUp();
    } catch (Throwable $exception)
    {
      error_log(
        'onNotSuccessfulTest cleanup error: ' . $exception->getMessage() . PHP_EOL,
        3,
        self::TEST_LOG_PATH
      );
    }
    parent::onNotSuccessfulTest($t);
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
          self::SCOPE_NONE
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
        self::SCOPE_NONE
      ]
    );
  }

  /**
   * @throws OtraException
   */
  public function testBuildDev_FilesProcessed(): void
  {
    // context
    mkdir(self::TEST_CONFIG_PATH, 0777, true);
    file_put_contents(self::TEST_ROUTES_PATH, '<?php return [];');

    // assertions
    $this->expectOutputString(
      'Class mapping finished' . SUCCESS .
      'Launching routes update...' . PHP_EOL . CLI_TABLE . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT . 
      'bundles/config/Routes.php' . CLI_BASE . ' updated' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_BASE . 'Files have been generated' . SUCCESS);

    // launching
    TasksManager::execute(
      $this->tasksClassMap,
      self::TASK_NAME,
      [
        self::OTRA_BINARY_NAME,
        self::TASK_NAME
      ]
    );
  }
}
