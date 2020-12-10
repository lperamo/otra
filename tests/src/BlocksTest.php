<?php
declare(strict_types=1);

namespace src;

use otra\{Controller, OtraException};
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
    $_SERVER['REQUEST_URI'] = '';
    self::$controller = new Controller(
      [
        'pattern' => '',
        'bundle' => '',
        'module' => '',
        'controller' => 'test',
        'action' => 'testAction',
        'route' => 'routeTest',
        'hasJsToLoad' => false,
        'hasCssToLoad' => false
      ]
    );
  }

  /**
   * Use of blocks without override
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testSimpleBlockSystem() : void
  {
    $content = self::$controller->renderView(
      TEST_PATH . 'src/bundles/views/simpleLayout.phtml',
      [],
      false,
      false
    );
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
    $content = self::$controller->renderView(
      TEST_PATH . 'src/bundles/views/advancedLayout.phtml',
      [],
      false,
      false
    );
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
    $content = self::$controller->renderView(
      TEST_PATH . 'src/bundles/views/complexLayout.phtml',
      [],
      false,
      false
    );
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
    $content = self::$controller->renderView(
      TEST_PATH . 'src/bundles/views/completeLayout.phtml',
      [],
      false,
      false
    );
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
    $content = self::$controller->renderView(
      TEST_PATH . 'src/bundles/views/evenMoreCompleteLayout.phtml',
      [],
      false,
      false
    );
    self::assertEquals('<!DOCTYPE html><html lang="en"><title>
  Welcome to the OTRA!</title><meta http-equiv="Accept" /><meta charset="UTF-8" /><link rel="prefetch" /><body>
  Hello World!
        test
      Hello World!after</body>
', $content);
  }

  public function testAnotherLayout():void
  {
    define('OTRA_TEST_ANOTHER_LAYOUT', 'anotherLayout.phtml');
    self::assertEquals(
      file_get_contents(TEST_PATH . 'src/bundles/views/backups/' . OTRA_TEST_ANOTHER_LAYOUT),
      self::$controller->renderView(
        TEST_PATH . 'src/bundles/views/' . OTRA_TEST_ANOTHER_LAYOUT,
        [],
        false,
        false
      )
    );
  }
}
