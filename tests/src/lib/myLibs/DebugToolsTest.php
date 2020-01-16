<?php

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DebugToolsTest extends TestCase
{
  const LOG_PATH = BASE_PATH . 'logs/';
  const DUMP_STRING = '<pre><p style="color:#3377FF">OTRA DUMP - ' . __FILE__ . ':';
  const DUMP_STRING_SECOND = '</p>/var/www/html/perso/otra/lib/otra/debugTools.php:71:';
  const DUMP_BEGIN_THIRD = ") {\n  [0] =>\n  string(513) \"";

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    require CORE_PATH . 'debugTools.php';
  }

  /**
   * @depends testTailCustom
   *
   * @author Lionel Péramo
   */
  public function testLg() : void
  {
    lg('[OTRA_TEST_DEBUG_TOOLS_LG]');
    $this->assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_TEST_DEBUG_TOOLS_LG\]@',
      tailCustom(self::LOG_PATH . $_SERVER['APP_ENV'] . '/trace.txt', 1)
     );
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_NoParameters() : void
  {
    $this->expectOutputString(
      '<pre><p style="color:#3377FF">OTRA DUMP - ' . __FILE__ . ':' . (__LINE__ + 2) . '</p></pre>'
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
      . "\n  (more elements)...\n}\n<br /></pre>"
    );

      dump(
        false,
        false,
        $arrayToTest
      );
    $this->assertFalse(defined('XDEBUG_VAR_DISPLAY_MAX_DATA'));
    $this->assertFalse(defined('XDEBUG_VAR_DISPLAY_MAX_CHILDREN'));
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
      . "\n  (more elements)...\n}\n<br /></pre>"
    );

    dump(
      true,
      false,
      $arrayToTest
    );
    $this->assertTrue(defined('XDEBUG_VAR_DISPLAY_MAX_DATA'));
    $this->assertFalse(defined('XDEBUG_VAR_DISPLAY_MAX_CHILDREN'));
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
      . "}\n<br /></pre>"
    );

    dump(
      true,
      true,
      $arrayToTest
    );
    $this->assertTrue(defined('XDEBUG_VAR_DISPLAY_MAX_DATA'));
    $this->assertTrue(defined('XDEBUG_VAR_DISPLAY_MAX_CHILDREN'));
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
    $dataToShow = ['test' => 'autre test'];
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
     </tbody></table><table class="test"><tbody><tr class="no-dummy" ><td>test</td><td colspan="2">\'autre test\'</td></tr></tbody></table>',
      createShowableFromArray($dataToShow, 'title')
    );
  }

  public function testConvertArrayToShowableConsole() : void
  {
    $dataToShow = ['test' => 'autre test'];
    createShowableFromArray($dataToShow, 'title');
    $this->markTestIncomplete('This function is not finished so we cannot finish the test.');
  }
}
