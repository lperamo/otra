<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\ClearSQLLogsAction;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, DEV, OTRA_PROJECT};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use function otra\tools\delTree;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ClearSqlLogsActionTest extends TestCase
{
  private const string
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    LOGS_DEV_PATH = BASE_PATH . 'logs/' . 'dev/',
    DEV_SQL_LOG = self::LOGS_DEV_PATH . 'sql.txt';

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = DEV;

    if (!file_exists(self::LOGS_DEV_PATH))
      mkdir(self::LOGS_DEV_PATH, 0777, true);

    if (!file_exists(self::DEV_SQL_LOG))
      touch(self::DEV_SQL_LOG);
  }

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  /** Cleaning all the files and folders that have been created */
  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (!OTRA_PROJECT)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      delTree(self::HELLO_WORLD_BUNDLE_PATH);
      delTree(BUNDLES_PATH . 'config/');
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @medium
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testClearSqlLogsAction() : void
  {
    // launching
    ob_start();
    new ClearSQLLogsAction([
      'pattern' => '/dbg/clearSQLLogs',
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'profiler',
      'action' => 'clearSQLLogsAction',
      'route' => 'otra_clearSQLLogs',
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    self::assertSame(
      'No more stored queries in ' . BASE_PATH . 'logs/dev/sql.txt.',
      $output,
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . 'ClearSQLLogsAction' . CLI_ERROR . ' page output...'
    );
  }
}
