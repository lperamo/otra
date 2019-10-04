<?

use lib\myLibs\{Controller, LionelException};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BlocksTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    define('CACHE_PATH', BASE_PATH . 'cache/');
    define('VERSION', 'v1');
  }

  /**
   * Use of blocks without override
   *
   * @throws LionelException
   * @author Lionel Péramo
   */
  public function testSimpleBlockSystem()
  {
    $controller = new Controller();
    $content = $controller->renderView(BASE_PATH . 'tests/src/bundles/views/simpleLayout.phtml');
    $this->assertEquals("<!DOCTYPE html><title>
    Welcome to OTRA!
  </title><html><body>
  Hello!
</body>
", $content);
  }

  /**
   * Use of overridden blocks and an inline block.
   *
   * @throws LionelException
   * @author Lionel Péramo
   */
  public function testAdvancedBlockSystem()
  {
    $controller = new Controller();
    $content = $controller->renderView(BASE_PATH . 'tests/src/bundles/views/advancedLayout.phtml');
    $this->assertEquals("<!DOCTYPE html><title>
  Welcome to the OTRA!</title><html><body>
  Hello World!
</body>
", $content);
  }

  /**
   * Use overridden blocks, an inline block, alternate blocks between blocks override and a parent block call.
   *
   * @throws LionelException
   * @author Lionel Péramo
   */
  public function testComplexLayout()
  {
    $controller = new Controller();
    $content = $controller->renderView(BASE_PATH . 'tests/src/bundles/views/complexLayout.phtml');
    $this->assertEquals("<!DOCTYPE html><title>
  Welcome to the OTRA!</title><html><body>
  Hello World!
    test
        </body>
", $content);
  }
}
