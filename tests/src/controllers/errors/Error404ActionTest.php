<?php
declare(strict_types=1);

namespace src\controllers\errors;

use otra\console\TasksManager;
use otra\controllers\errors\Error404Action;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use function otra\tools\files\returnLegiblePath2;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, OTRA_PROJECT, PROD, TEST_PATH};
use function otra\tools\delTree;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class Error404ActionTest extends TestCase
{
  private const string
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld';

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
    require CORE_PATH . 'tools/files/returnLegiblePath.php';
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  /**
   * (should be medium not large)
   * @dataProvider dataProvider
   * @large
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testError404Action(?string $ipAddress, string $expectedOutput) : void
  {
    // context
    $_SERVER['HTTP_HOST'] = 'dev.otra-framework.tech';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REMOTE_ADDR'] = $ipAddress;

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

    ob_start();
    require $expectedOutput;

    // testing
    self::assertSame(
      ob_get_clean(),
      $output,
      'Testing 404 error page output against ' . returnLegiblePath2($expectedOutput)
    );
  }

  /**
   * @return array<string, array{0: null|string, 1: string}>
   */
  public static function dataProvider(): array
  {
    return [
      'local' => ['::1', TEST_PATH . 'examples/error404/local.phtml'],
      'online' => [null, TEST_PATH . 'examples/error404/online.phtml']
    ];
  }
}
