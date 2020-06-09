<?php
declare(strict_types=1);

namespace src;

use otra\{MasterController, Controller, OtraException};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BlocksTest extends TestCase
{
  private static Controller $controller;

  /**
   * @throws \ReflectionException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'prod';
    define('VERSION', 'v1');
    self::$controller = new Controller();
    self::$controller->route = 'routeTest';
    removeFieldScopeProtection(MasterController::class, 'hasCssToLoad')->setValue(false);
    removeFieldScopeProtection(MasterController::class, 'hasJsToLoad')->setValue(false);
  }

  /**
   * Use of blocks without override
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSimpleBlockSystem() : void
  {
    $content = self::$controller->renderView(TEST_PATH . 'src/bundles/views/simpleLayout.phtml');
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
    Welcome to OTRA!
  </title><body>
  Hello!
</body>
', $content);
  }

  /**
   * Use of overridden blocks and an inline block.
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testAdvancedBlockSystem() : void
  {
    $content = self::$controller->renderView(TEST_PATH . 'src/bundles/views/advancedLayout.phtml');
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
  Welcome to the OTRA!</title><body>
  Hello World!
</body>
', $content);
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
  public function testComplexLayout() : void
  {
    $content = self::$controller->renderView(TEST_PATH . 'src/bundles/views/complexLayout.phtml');
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
  Welcome to the OTRA!</title><body>
  Hello World!
        test
    </body>
', $content);
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
  public function testCompleteLayout() : void
  {
    $content = self::$controller->renderView(TEST_PATH . 'src/bundles/views/completeLayout.phtml');
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
  Welcome to the OTRA!</title><body>
  Hello World!
        test
      Hello World!after</body>
', $content);
  }

  /**
   * Use :
   * - overridden blocks,
   * - an inline title block,
   * - alternate blocks between blocks override
   * - parent block call
   * - the end of the content after a child block is not empty
   * - empty block placeholders
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testEvenMoreCompleteLayout() : void {
    $content = self::$controller->renderView(TEST_PATH . 'src/bundles/views/evenMoreCompleteLayout.phtml');
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
  Welcome to the OTRA!</title><meta http-equiv="Accept" /><meta charset="UTF-8" /><link rel="prefetch" /><body>
  Hello World!
        test
      Hello World!after</body>
', $content);
  }
}
