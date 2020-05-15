<?php
namespace src;

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DebugToolsTest extends TestCase
{
  const LOG_PATH = BASE_PATH . 'logs/',
    DUMP_STRING = 'OTRA DUMP - ' . __FILE__ . ':',
    DUMP_STRING_SECOND = "\n" . '/var/www/html/perso/otra/src/debugTools.php:86:',
    DUMP_BEGIN_THIRD = ") {\n  [0] =>\n  string(513) \"";

  private static string $LOGS_PROD_PATH;

  public static function setUpBeforeClass(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    self::$LOGS_PROD_PATH = self::LOG_PATH . $_SERVER['APP_ENV'];

    // @TODO we should be able to do a simple require and not require_once
    require_once CORE_PATH . 'debugTools.php';

    if (file_exists(self::$LOGS_PROD_PATH) === false)
      mkdir(self::$LOGS_PROD_PATH, 0777, true);
  }

  public static function tearDownAfterClass(): void
  {
    if (OTRA_PROJECT === false)
    {
      if (file_exists(self::$LOGS_PROD_PATH))
        rmdir(self::$LOGS_PROD_PATH);

      if (file_exists(self::LOG_PATH))
        rmdir(self::LOG_PATH);
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
    $traceLogFile = self::LOG_PATH . $_SERVER['APP_ENV'] . '/trace.txt';
    $this->assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_TEST_DEBUG_TOOLS_LG\]@',
      tailCustom($traceLogFile, 1)
     );

    // cleaning
    unlink($traceLogFile);
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_NoParameters() : void
  {
    $this->expectOutputString(
      'OTRA DUMP - ' . __FILE__ . ':' . (__LINE__ + 2) . PHP_EOL
    );
    dump();
  }

  /**
   * @param int $maxData
   * @param int $maxChildren
   *
   * @return array
   * @author Lionel Péramo
   */
  private function getDumpTestArray(int $maxData, int $maxChildren) : array
  {
    $arrayToTest = array_fill(0, $maxChildren + 1, 0);
    $arrayToTest[0] = str_repeat('0', $maxData + 1);

    return $arrayToTest;
  }

  /**
   * @param int $maxChildren
   *
   * @return string
   * @author Lionel Péramo
   */
  private function getExpectedOutputPartial(int $maxChildren) : string
  {
    $expectedOutputPartial = '';

    for($i = 1; $i < $maxChildren; ++$i)
    {
      $expectedOutputPartial .= '  [' . $i . '] =>' . "\n  int(0)\n";
    }

    return $expectedOutputPartial;
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxDataFalseMaxChildrenFalse() : void
  {
    $maxData = (int) ini_get('xdebug.var_display_max_data');
    $maxChildren = (int) ini_get('xdebug.var_display_max_children');
    $arrayToTest = $this->getDumpTestArray($maxData, $maxChildren);

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . ($maxChildren + 1) . self::DUMP_BEGIN_THIRD . str_repeat(0,$maxData) . "\"...\n"
      . $this->getExpectedOutputPartial($maxChildren)
      . "\n  (more elements)...\n}\n"
    );

      dump(
        false,
        false,
        $arrayToTest
      );
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxDataTrueMaxChildrenFalse() : void
  {
    $maxData = (int) ini_get('xdebug.var_display_max_data');
    $maxChildren = (int) ini_get('xdebug.var_display_max_children');
    $arrayToTest = $this->getDumpTestArray($maxData, $maxChildren);

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . ($maxChildren + 1) . self::DUMP_BEGIN_THIRD . str_repeat(0, $maxData + 1) . "\"\n"
      . $this->getExpectedOutputPartial($maxChildren)
      . "\n  (more elements)...\n}\n"
    );

    dump(
      true,
      false,
      $arrayToTest
    );
  }

  public function testDump_MaxDataTrueMaxChildrenTrue() : void
  {
    $maxData = (int) ini_get('xdebug.var_display_max_data');
    $maxChildren = (int) ini_get('xdebug.var_display_max_children');
    $arrayToTest = $this->getDumpTestArray($maxData, $maxChildren);

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . ($maxChildren + 1) . self::DUMP_BEGIN_THIRD . str_repeat(0, $maxData + 1) . "\"\n"
      . $this->getExpectedOutputPartial($maxChildren + 1)
      . "}\n"
    );

    dump(
      true,
      true,
      $arrayToTest
    );
  }

  /**
   * Lionel Péramo
   */
  public function testGetCaller() : void
  {
    $test = function(){ return getCaller(); };
    $this->assertEquals(__FILE__ . ':' . __LINE__, $test());
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
    $this->assertEquals(
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
    $this->assertEquals(
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
    $this->markTestIncomplete('This function is not finished so we cannot finish the test.');
  }
}
