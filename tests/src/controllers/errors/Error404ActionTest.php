<?php
declare(strict_types=1);

namespace src\controllers\errors;

use otra\console\TasksManager;
use otra\controllers\errors\Error404Action;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, OTRA_PROJECT, PROD, TEST_PATH};
use function otra\tools\delTree;

/**
 * @runTestsInSeparateProcesses
 */
class Error404ActionTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld';

  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = PROD;
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

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testError404Action() : void
  {
    // context
    $_SERVER['HTTP_HOST'] = 'dev.otra-framework.tech';
    $_SERVER['REQUEST_URI'] = '/';

    // launching
    ob_start();
    new Error404Action([
      'pattern' => '/404',
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'errors',
      'action' => 'error404Action',
      'route' => 'otra_404',
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    self::assertSame(
      file_get_contents(TEST_PATH . 'examples/error404.phtml'),
      $output,
      'Testing 404 error page output...'
    );
  }
}
