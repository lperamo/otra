<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\RoutesAction;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use Throwable;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\
{APP_ENV, BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, DEV, OTRA_PROJECT, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use function otra\tools\delTree;
use const otra\config\VERSION;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class RoutesActionTest extends TestCase
{
  private const string
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    TEST_TEMPLATE = TEST_PATH . 'examples/profiler/routesAction.phtml',
    TEST_LOG_PATH = BASE_PATH . 'logs/dev/phpunit.txt';

  /**
   * Private method to clean up created files and directories.
   */
  private function cleanup() : void
  {
    if (!OTRA_PROJECT)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      // Delete HelloWorld bundle folder
      if (file_exists(self::HELLO_WORLD_BUNDLE_PATH) && !delTree(self::HELLO_WORLD_BUNDLE_PATH))
        error_log(
          'Error while deleting HelloWorld bundle folder' . PHP_EOL,
          3,
          self::TEST_LOG_PATH
        );

      // Delete config folder within bundles
      if (file_exists(BUNDLES_PATH . 'config/') && !delTree(BUNDLES_PATH . 'config/'))
        error_log('Error while deleting config folder' . PHP_EOL, 3, self::TEST_LOG_PATH);

      // Remove bundles directory if exists
      if (is_dir(BASE_PATH . 'bundles') && !rmdir(BASE_PATH . 'bundles'))
        error_log(
          'Error while removing bundles directory' . PHP_EOL,
          3,
          self::TEST_LOG_PATH
        );

      // Delete CSS cache file
      $cacheHash = sha1('ca' . 'HelloWorld' . VERSION . 'che');
      $cacheCssFile = CACHE_PATH . 'css/' . $cacheHash . '.br';

      if (file_exists($cacheCssFile) && !unlink($cacheCssFile))
        error_log(
          'Error while unlinking CSS cache file: ' . $cacheCssFile . PHP_EOL,
          3,
          self::TEST_LOG_PATH
        );

      // Delete print CSS cache file
      $cachePrintCssFile = CACHE_PATH . 'css/print_' . $cacheHash . '.br';

      if (file_exists($cachePrintCssFile) && !unlink($cachePrintCssFile))
        error_log(
          'Error while unlinking print CSS cache file: ' . $cachePrintCssFile . PHP_EOL,
          3,
          self::TEST_LOG_PATH
        );
    }
  }

  /**
   * Set up the environment for the test.
   * @throws OtraException
   */
  protected function setUp() : void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = DEV;

    // Register a shutdown function to ensure cleanup on abrupt termination.
    register_shutdown_function(function ()
    {
      try
      {
        $this->cleanup();
      } catch (Throwable $exception)
      {
        error_log(
          'Shutdown cleanup error: ' . $exception->getMessage() . PHP_EOL,
          3,
          self::TEST_LOG_PATH
        );
      }
    });

    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  /**
   * Tear down the test environment and perform cleanup.
   */
  protected function tearDown() : void
  {
    try
    {
      $this->cleanup();
    }
    catch (Throwable $exception)
    {
      error_log(
        'TearDown cleanup error: ' . $exception->getMessage() . PHP_EOL,
        3,
        self::TEST_LOG_PATH
      );
    }

    parent::tearDown();
  }

  /**
   * Called when a test fails to ensure cleanup is executed.
   *
   * @param Throwable $t
   * @throws Throwable
   */
  protected function onNotSuccessfulTest(Throwable $t) : never
  {
    try
    {
      $this->cleanup();
    }
    catch (Throwable $exception)
    {
      error_log(
        'onNotSuccessfulTest cleanup error: ' . $exception->getMessage() . PHP_EOL,
        3,
        self::TEST_LOG_PATH
      );
    }
    parent::onNotSuccessfulTest($t);
  }

  /**
   * Test to verify the output of RoutesAction.
   * Routes are showing files that are present on the production side, but it doesn't check on the development side.
   * So, it can be misleading.
   * This must change in the future.
   *
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

    // launching
    ob_start();
    $routesAction = new RoutesAction([
      'pattern' => '/profiler/sql',
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'profiler',
      'action' => 'routesAction',
      'route' => 'otra_routes',
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    ob_start();
    require self::TEST_TEMPLATE;
    self::assertInstanceOf(RoutesAction::class, $routesAction);
    self::assertSame(
      ob_get_clean(),
      $output,
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . 'routesAction' . CLI_ERROR . ' page output with ' .
      CLI_INFO_HIGHLIGHT . self::TEST_TEMPLATE . CLI_ERROR . '...'
    );
  }
}
