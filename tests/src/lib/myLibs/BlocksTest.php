<?php

use lib\myLibs\{Controller, OtraException};
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
   * @throws OtraException
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
   * @throws OtraException
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
   * Use :
   * - overridden blocks,
   * - an inline title block,
   * - alternate blocks between blocks override and a parent block call.
   *
   * @throws OtraException
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

  /**
   * Use :
   * - overridden blocks,
   * - an inline title block,
   * - alternate blocks between blocks override
   * - parent block call
   * - the end of the content after a child block is not empty
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testCompleteLayout()
  {
    $controller = new Controller();
    $content = $controller->renderView(BASE_PATH . 'tests/src/bundles/views/completeLayout.phtml');
    $this->assertEquals("<!DOCTYPE html><title>
  Welcome to the OTRA!</title><html><body>
  Hello World!
        test
      Hello World!after</body>
", $content);
  }
}
