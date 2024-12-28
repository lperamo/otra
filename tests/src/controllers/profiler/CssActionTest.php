<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\CssAction;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\
{APP_ENV, BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, DEV, OTRA_PROJECT, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use function otra\tools\delTree;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class CssActionTest extends TestCase
{
  private const string
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    ACTION = 'css',
    FULL_ACTION_NAME = self::ACTION . 'Action',
    SASS_TREE_CACHE_PATH = CACHE_PATH . 'css/sassTree.php',
    TEST_TEMPLATE = TEST_PATH . 'examples/profiler/' . self::FULL_ACTION_NAME. '.phtml';

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
  public function test_noSassCache() : void
  {
    // context
    require CORE_PATH . 'templating/blocks.php';
    $_GET['route'] = 'HelloWorld';
    $_SERVER['HTTP_HOST'] = 'https://dev.otra-framework.tech';

    if (file_exists(self::SASS_TREE_CACHE_PATH))
      unlink(self::SASS_TREE_CACHE_PATH);

    // launching
    ob_start();
    $cssAction = new CssAction([
      'pattern' => '/profiler/' . self::ACTION,
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'profiler',
      'action' => self::FULL_ACTION_NAME,
      'route' => 'otra_' . self::ACTION,
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    self::assertInstanceOf(CssAction::class, $cssAction);
    ob_start();
    require self::TEST_TEMPLATE;
    self::assertSame(
      ob_get_clean(),
      $output,
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . self::FULL_ACTION_NAME . CLI_ERROR . ' page output with ' .
      CLI_INFO_HIGHLIGHT . self::TEST_TEMPLATE . CLI_ERROR . '...'
    );
  }
}
