<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\LogsAction;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, DEV, OTRA_PROJECT, PROD, TEST_PATH};
use function otra\tools\delTree;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class LogsActionTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    TEST_TEMPLATE = TEST_PATH . 'examples/profiler/logsAction.phtml',
    LOGS_PATH = BASE_PATH . 'logs/',
    LOGS_DEV_PATH = self::LOGS_PATH . DEV . '/',
    LOGS_PROD_PATH = self::LOGS_PATH . PROD . '/',
    LOG_DEV_TRACE = self::LOGS_DEV_PATH . 'trace.txt',
    LOG_PROD_CLASS_NOT_FOUND = self::LOGS_PROD_PATH . 'classNotFound.txt',
    LOG_PROD_CLASSIC_LOG = self::LOGS_PROD_PATH . 'log.txt',
    LOG_PROD_UNKNOWN_EXCEPTIONS = self::LOGS_PROD_PATH . 'unknownExceptions.txt',
    LOG_PROD_UNKNOWN_FATAL_ERRORS = self::LOGS_PROD_PATH . 'unknownFatalErrors.txt';

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = DEV;
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();

    foreach([
      self::LOG_DEV_TRACE,
      self::LOG_PROD_CLASSIC_LOG,
      self::LOG_PROD_CLASS_NOT_FOUND,
      self::LOG_PROD_UNKNOWN_EXCEPTIONS,
      self::LOG_PROD_UNKNOWN_FATAL_ERRORS
    ] as $fileToTruncate)
    {
      if (file_exists($fileToTruncate))
        file_put_contents($fileToTruncate, '');
    }
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
  public function test() : void
  {
    // context
    require CORE_PATH . 'templating/blocks.php';
    $_GET['route'] = 'HelloWorld';
    $_SERVER['HTTP_HOST'] = 'https://dev.otra-framework.tech';

    if (!file_exists(self::LOGS_DEV_PATH))
      mkdir(self::LOGS_DEV_PATH, 0755, true);

    if (!file_exists(self::LOGS_PROD_PATH))
      mkdir(self::LOGS_PROD_PATH);

    if (!file_exists(self::LOG_DEV_TRACE))
      touch(self::LOG_DEV_TRACE);

    if (!file_exists(self::LOG_PROD_CLASS_NOT_FOUND))
      touch(self::LOG_PROD_CLASS_NOT_FOUND);

    if (!file_exists(self::LOG_PROD_CLASSIC_LOG))
      touch(self::LOG_PROD_CLASSIC_LOG);

    if (!file_exists(self::LOG_PROD_UNKNOWN_EXCEPTIONS))
      touch(self::LOG_PROD_UNKNOWN_EXCEPTIONS);

    if (!file_exists(self::LOG_PROD_UNKNOWN_FATAL_ERRORS))
      touch(self::LOG_PROD_UNKNOWN_FATAL_ERRORS);

    // launching
    ob_start();
    $logsAction = new LogsAction([
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
    self::assertInstanceOf(LogsAction::class, $logsAction);
    ob_start();
    require self::TEST_TEMPLATE;
    self::assertSame(
      ob_get_clean(),
      $output,
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . 'logsAction' . CLI_ERROR . ' page output with ' .
      CLI_INFO_HIGHLIGHT . self::TEST_TEMPLATE . CLI_ERROR . '...'
    );
  }
}
