<?php
declare(strict_types=1);

namespace src;

use otra\{Controller, OtraException};
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV, PROD, TEST_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class BlocksTest extends TestCase
{
  private static Controller $controller;
  private const
    LAYOUTS_PATH = TEST_PATH . 'src/bundles/views/',
    BACKUPS_PATH = self::LAYOUTS_PATH . 'backups/';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
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
    define(__NAMESPACE__ . '\\SIMPLE_LAYOUT', 'simpleLayout.phtml');
    self::assertEquals(
      file_get_contents(self::BACKUPS_PATH . SIMPLE_LAYOUT),
      self::$controller->renderView(self::LAYOUTS_PATH . SIMPLE_LAYOUT, [], false, false)
    );
  }

  /**
   * Use of overridden blocks and an inline block.
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testAdvancedBlockSystem() : void
  {
    define(__NAMESPACE__ . '\\ADVANCED_LAYOUT', 'advancedLayout.phtml');
    self::assertEquals(
      file_get_contents(self::BACKUPS_PATH . ADVANCED_LAYOUT),
      self::$controller->renderView(self::LAYOUTS_PATH . ADVANCED_LAYOUT, [], false, false)
    );
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
    define(__NAMESPACE__ . '\\COMPLEX_LAYOUT', 'complexLayout.phtml');
    self::assertEquals(
      file_get_contents(self::BACKUPS_PATH . COMPLEX_LAYOUT),
      self::$controller->renderView(self::LAYOUTS_PATH . COMPLEX_LAYOUT, [], false, false)
    );
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
    define(__NAMESPACE__ . '\\COMPLETE_LAYOUT', 'completeLayout.phtml');
    self::assertEquals(
      file_get_contents(self::BACKUPS_PATH . COMPLETE_LAYOUT),
      self::$controller->renderView(self::LAYOUTS_PATH . COMPLETE_LAYOUT, [], false, false)
    );
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
    define(__NAMESPACE__ . '\\EVEN_MORE_COMPLETE_LAYOUT', 'evenMoreCompleteLayout.phtml');
    self::assertEquals(
      file_get_contents(self::BACKUPS_PATH . EVEN_MORE_COMPLETE_LAYOUT),
      self::$controller->renderView(self::LAYOUTS_PATH . EVEN_MORE_COMPLETE_LAYOUT, [], false, false)
    );
  }

  /**
   * Use :
   * - overridden blocks,
   * - an inline title block
   * - parent block call
   * - empty block placeholders
   * - replacing block inside a different kind of block (different block name)
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testAnotherLayout():void
  {
    define(__NAMESPACE__ . '\\OTRA_TEST_ANOTHER_LAYOUT', 'anotherLayout.phtml');
    define(__NAMESPACE__ . '\\BACKUP_ANOTHER_LAYOUT', self::BACKUPS_PATH . OTRA_TEST_ANOTHER_LAYOUT);
    define(__NAMESPACE__ . '\\TESTED_ANOTHER_LAYOUT', self::LAYOUTS_PATH . OTRA_TEST_ANOTHER_LAYOUT);
    self::assertEquals(
      file_get_contents(BACKUP_ANOTHER_LAYOUT),
      self::$controller->renderView(TESTED_ANOTHER_LAYOUT, [], false, false),
      'Testing ' . BACKUP_ANOTHER_LAYOUT . ' and ' . TESTED_ANOTHER_LAYOUT . ' ...'
    );
  }

  /**
   * Use :
   * - overridden blocks,
   * - inline blocks
   * - parent block call
   * - empty block placeholders
   * - replacing block inside a different kind of block (different block name)
   *
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testAnotherLayoutBis():void
  {
    define(__NAMESPACE__ . '\\OTRA_TEST_ANOTHER_LAYOUT', 'anotherLayoutBis.phtml');
    define(__NAMESPACE__ . '\\BACKUP_ANOTHER_LAYOUT', self::BACKUPS_PATH . OTRA_TEST_ANOTHER_LAYOUT);
    define(__NAMESPACE__ . '\\TESTED_ANOTHER_LAYOUT', self::LAYOUTS_PATH . OTRA_TEST_ANOTHER_LAYOUT);
    self::assertEquals(
      file_get_contents(BACKUP_ANOTHER_LAYOUT),
      self::$controller->renderView(TESTED_ANOTHER_LAYOUT, [], false, false),
      'Testing ' . BACKUP_ANOTHER_LAYOUT . ' and ' . TESTED_ANOTHER_LAYOUT . ' ...'
    );
  }

  /**
   * @throws OtraException
   */
  public function testReplacingBlocks():void
  {
    define(__NAMESPACE__ . '\\OTRA_TEST_REPLACING_BLOCKS', 'replacingBlocks.phtml');
    define(__NAMESPACE__ . '\\BACKUP_REPLACING_BLOCKS', self::BACKUPS_PATH . OTRA_TEST_REPLACING_BLOCKS);
    define(__NAMESPACE__ . '\\TESTED_REPLACING_BLOCKS', self::LAYOUTS_PATH . OTRA_TEST_REPLACING_BLOCKS);
    self::assertEquals(
      file_get_contents(BACKUP_REPLACING_BLOCKS),
      self::$controller->renderView(TESTED_REPLACING_BLOCKS, [], false, false),
      'Testing ' . BACKUP_REPLACING_BLOCKS . ' and ' . TESTED_REPLACING_BLOCKS . ' ...'
    );
  }
}
