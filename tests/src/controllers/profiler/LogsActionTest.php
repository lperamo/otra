<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\LogsAction;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, DEV, OTRA_PROJECT, TEST_PATH};
use function otra\tools\delTree;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};

/**
 * @runTestsInSeparateProcesses
 */
class LogsActionTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld';

  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = DEV;
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
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    require CORE_PATH . 'templating/blocks.php';
    $_GET['route'] = 'HelloWorld';
    $_SERVER['HTTP_HOST'] = 'https://dev.otra-framework.tech';
    file_put_contents(BASE_PATH . 'logs/' . DEV . '/trace.txt', '');

    // launching
    ob_start();
    new LogsAction([
      'pattern' => '/profiler/logs',
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'profiler',
      'action' => 'logsAction',
      'route' => 'otra_logs',
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    self::assertEquals(
      file_get_contents(TEST_PATH . 'examples/profiler/logsAction.phtml'),
      $output,
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . 'logsAction' . CLI_ERROR . ' page output...'
    );
  }
}
