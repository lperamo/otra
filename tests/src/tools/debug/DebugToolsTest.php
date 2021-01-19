<?php
declare(strict_types=1);

namespace src;

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DebugToolsTest extends TestCase
{
  private const LOG_PATH = BASE_PATH . 'logs/',
    DUMP_STRING = 'OTRA DUMP - ' . __FILE__ . ':',
    DUMP_STRING_SECOND = "\n" . '/var/www/html/perso/otra/src/tools/debug/dump.php:110:',
    DUMP_BEGIN_THIRD = ") {\n  [0] =>\n  string(11) \"",
    XDEBUG_KEY_MAX_DATA = 'xdebug.var_display_max_data',
    XDEBUG_KEY_MAX_DEPTH = 'xdebug.var_display_max_depth',
    XDEBUG_KEY_MAX_CHILDREN = 'xdebug.var_display_max_children',
    XDEBUG_TEST_VALUE_MAX_CHILDREN = 5,
    XDEBUG_TEST_VALUE_MAX_DATA = 10,
    XDEBUG_TEST_VALUE_MAX_DEPTH = 3;

  private static string $LOGS_PROD_PATH;
  private static bool $outputFlag = true;

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = 'prod';
    self::$LOGS_PROD_PATH = self::LOG_PATH . $_SERVER[APP_ENV];

    // @TODO we should be able to do a simple require and not require_once
    require_once CORE_PATH . 'tools/debug/dump.php';

    if (file_exists(self::$LOGS_PROD_PATH) === false)
      mkdir(self::$LOGS_PROD_PATH, 0777, true);
  }

  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();

    if (OTRA_PROJECT === false)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      if (file_exists(self::LOG_PATH))
        $delTree(self::LOG_PATH);
    }
  }

  /**
   * @depends testTailCustom
   *
   * @author Lionel Péramo
   */
  public function testLg() : void
  {
    lg('[OTRA_TEST_DEBUG_TOOLS_LG]');
    $traceLogFile = self::LOG_PATH . $_SERVER[APP_ENV] . '/trace.txt';
    self::assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_TEST_DEBUG_TOOLS_LG\]@',
      tailCustom($traceLogFile, 1)
     );

    // cleaning
    unlink($traceLogFile);
  }

  /**
   * Lionel Péramo
   */
  public function testGetCaller() : void
  {
    $testGetCaller = function(){ return getCaller(); };
    self::assertEquals(__FILE__ . ':' . __LINE__, $testGetCaller());
  }

  /**
   * @author Lionel Péramo
   */
  public function testTailCustom() : void
  {
    self::assertEquals('world', tailCustom(BASE_PATH . 'tests/testTail.txt', 1));
  }

  /**
   * Test with no blank line at the end of the test text file.
   *
   * @author Lionel Péramo
   */
  public function testTailCustom_NoEndBlankLine() : void
  {
    self::assertEquals('world', tailCustom(BASE_PATH . 'tests/testTailNoEndBlankLine.txt', 1));
  }

  /**
   * @author Lionel Péramo
   */
  public function testReformatSource() : void
  {
    require CORE_PATH . 'tools/reformatSource.php';

    self::assertEquals(
      '&lt;p&gt;Hi&lt;/p&gt;<br/>&lt;p&gt;Ha&lt;/p&gt;',
      reformatSource('<p>Hi</p><p>Ha</p>')
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testConvertArrayToShowable() : void
  {
    $dataToShow = ['test' => 'other test'];
    self::assertEquals(
      '    <table class="test innerHeader">
      <thead>
        <tr>
          <th colspan="3">title</th>
        </tr>
        <tr>
          <th>Name</th>
          <th>Index or value if array</th>
          <th>Value if array</th>
        </tr>
      </thead>
      <tbody>
     </tbody></table><table class="test"><tbody><tr class="no-dummy" ><td>test</td><td colspan="2">\'other test\'</td></tr></tbody></table>',
      createShowableFromArray($dataToShow, 'title')
    );
  }

  public function testConvertArrayToShowableConsole() : void
  {
    $dataToShow = ['test' => 'other test'];
    createShowableFromArray($dataToShow, 'title');
    self::markTestIncomplete('This function is not finished so we cannot finish the test.');
  }
}
