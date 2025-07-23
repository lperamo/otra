<?php
declare(strict_types=1);

namespace src\templating;

use otra\{Controller, OtraException};
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV, CORE_PATH, PROD, TEST_PATH};
use function otra\tools\files\returnLegiblePath2;

/**
 * @runTestsInSeparateProcesses
 */
class BlocksTest extends TestCase
{
  private static Controller $controller;
  private const string
    LAYOUTS_PATH = TEST_PATH . 'src/bundles/views/',
    BACKUPS_PATH = self::LAYOUTS_PATH . 'backups/',
    LABEL_COMPARING_THE_RENDERED_VIEW = 'Comparing the rendered view ',
    LABEL_AGAINST = ' against ';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'test';
    $_SERVER['REQUEST_URI'] = '';
    require CORE_PATH . 'tools/files/returnLegiblePath.php';

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
    ob_start();
    require self::BACKUPS_PATH . SIMPLE_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(self::LAYOUTS_PATH . SIMPLE_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(self::LAYOUTS_PATH . SIMPLE_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(self::BACKUPS_PATH . SIMPLE_LAYOUT)
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
    ob_start();
    require self::BACKUPS_PATH . ADVANCED_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(self::LAYOUTS_PATH . ADVANCED_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(self::LAYOUTS_PATH . ADVANCED_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(self::BACKUPS_PATH . ADVANCED_LAYOUT)
    );
  }

  /**
   * Use:
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
    ob_start();
    require self::BACKUPS_PATH . COMPLEX_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(self::LAYOUTS_PATH . COMPLEX_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(self::LAYOUTS_PATH . COMPLEX_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(self::BACKUPS_PATH . COMPLEX_LAYOUT)
    );
  }

  /**
   * Use:
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
    ob_start();
    require self::BACKUPS_PATH . COMPLETE_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(self::LAYOUTS_PATH . COMPLETE_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(self::LAYOUTS_PATH . COMPLETE_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(self::BACKUPS_PATH . COMPLETE_LAYOUT)
    );
  }

  /**
   * Use:
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
    ob_start();
    require self::BACKUPS_PATH . EVEN_MORE_COMPLETE_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(self::LAYOUTS_PATH . EVEN_MORE_COMPLETE_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW .
      returnLegiblePath2(self::LAYOUTS_PATH . EVEN_MORE_COMPLETE_LAYOUT) . self::LABEL_AGAINST .
      returnLegiblePath2(self::BACKUPS_PATH . EVEN_MORE_COMPLETE_LAYOUT)
    );
  }

  /**
   * Use:
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

    ob_start();
    require BACKUP_ANOTHER_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(TESTED_ANOTHER_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(TESTED_ANOTHER_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(BACKUP_ANOTHER_LAYOUT)
    );
  }

  /**
   * Use:
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

    ob_start();
    require BACKUP_ANOTHER_LAYOUT;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(TESTED_ANOTHER_LAYOUT, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(TESTED_ANOTHER_LAYOUT) .
      self::LABEL_AGAINST . returnLegiblePath2(BACKUP_ANOTHER_LAYOUT)
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

    ob_start();
    require BACKUP_REPLACING_BLOCKS;

    self::assertSame(
      ob_get_clean(),
      self::$controller->renderView(TESTED_REPLACING_BLOCKS, [], false, false),
      self::LABEL_COMPARING_THE_RENDERED_VIEW . returnLegiblePath2(TESTED_REPLACING_BLOCKS) .
      self::LABEL_AGAINST . returnLegiblePath2(BACKUP_REPLACING_BLOCKS)
    );
  }
}
