<?php
declare(strict_types=1);

namespace src;

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DumpTest extends TestCase
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
   * Updates the depth of the array. For a depth of 3, we will see 3 levels of KEYS (2 levels only of values).
   *
   * @param int   $depthIndex
   * @param array $array
   *
   * @return array
   */
  public static function fillArrayDepth(int &$depthIndex, array &$array) : array
  {
    while($depthIndex < self::XDEBUG_TEST_VALUE_MAX_DEPTH - 1)
    {
      $array[0] = [0 => ''];
      ++$depthIndex;
      self::fillArrayDepth($depthIndex, $array[0]);
    }

    return $array;
  }

  /**
   * Force test values and returns an array to test those values
   *
   *
   * @return array
   * @author Lionel Péramo
   */
  private function getDumpTestArray() : array
  {
    // We force test values
    ini_set(self::XDEBUG_KEY_MAX_CHILDREN, strval(self::XDEBUG_TEST_VALUE_MAX_CHILDREN));
    ini_set(self::XDEBUG_KEY_MAX_DATA, strval(self::XDEBUG_TEST_VALUE_MAX_DATA));
    ini_set(self::XDEBUG_KEY_MAX_DEPTH, strval(self::XDEBUG_TEST_VALUE_MAX_DEPTH));

    // for children test
    $arrayToTest = array_fill(0, self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1, 0);

    // for data length test
    $arrayToTest[0] = str_repeat('0', self::XDEBUG_TEST_VALUE_MAX_DATA + 1);

    // for depth test
    $arrayToTest[1] = [0 => ''];
    $depthIndex = 0;
    self::fillArrayDepth($depthIndex, $arrayToTest[1]);

    return $arrayToTest;
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
   * @param int    $depth
   *
   * @param string $output
   *
   * @param bool   $reachDepth
   *
   * @return string
   */
  public static function increaseExpectedArrayDepth(int &$depth, string &$output, bool $reachDepth = false) : string
  {
    while ($depth < self::XDEBUG_TEST_VALUE_MAX_DEPTH)
    {
      $spaceLength = str_repeat(' ', ($depth + 1) * 2);
      $output .= $spaceLength . ($depth === 0 ? '[1] =>' : '[0] =>') . PHP_EOL . $spaceLength . 'array(1) {' . PHP_EOL;

      $oldDepth = $depth;
      ++$depth;
      self::increaseExpectedArrayDepth($depth, $output, $reachDepth);

      // If we had stop increasing the array depth...
      if (self::$outputFlag)
      {
        $output .= $spaceLength . ($reachDepth ? '  [0] =>' . PHP_EOL . $spaceLength . '  string(0) ""' : '  ...') . PHP_EOL;
        self::$outputFlag = false;
      }

      $output .= str_repeat(' ', ($oldDepth + 1) * 2) . '}' . PHP_EOL;
    }

    return $output;
  }

  /**
   * @param int  $maxChildren
   * @param bool $reachDepth
   *
   * @return string
   * @author Lionel Péramo
   */
  private function getExpectedOutputPartial(int $maxChildren, bool $reachDepth = false) : string
  {
    $tempOutput = $expectedOutputPartial = '';
    $depth = 0;
    $expectedOutputPartial .= self::increaseExpectedArrayDepth($depth, $tempOutput, $reachDepth);
    self::$outputFlag = true;

    for($index = 2; $index < $maxChildren; ++$index)
    {
      $expectedOutputPartial .= '  [' . $index . '] =>' . "\n  int(0)\n";
    }

    return $expectedOutputPartial;
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxChildrenFalseMaxDataFalseMaxDepthFalse() : void
  {
    $maxChildren = (int) ini_get(self::XDEBUG_KEY_MAX_CHILDREN);
    $maxData = (int) ini_get(self::XDEBUG_KEY_MAX_DATA);
    $maxDepth = (int) ini_get(self::XDEBUG_KEY_MAX_DEPTH);
    $arrayToTest = $this->getDumpTestArray();

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . (self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1) . self::DUMP_BEGIN_THIRD . str_repeat('0', self::XDEBUG_TEST_VALUE_MAX_DATA) . "\"...\n"
      . $this->getExpectedOutputPartial(self::XDEBUG_TEST_VALUE_MAX_CHILDREN)
      . "\n  (more elements)...\n}\n"
    );

    dump(
      [false, false, false],
      $arrayToTest
    );

    // We restore the values
    ini_set(self::XDEBUG_KEY_MAX_CHILDREN, strval($maxChildren));
    ini_set(self::XDEBUG_KEY_MAX_DATA, strval($maxData));
    ini_set(self::XDEBUG_KEY_MAX_DEPTH, strval($maxDepth));
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxChildrenFalseMaxDataTrueMaxDepthFalse() : void
  {
    // We store old values
    $maxChildren = (int) ini_get(self::XDEBUG_KEY_MAX_CHILDREN);
    $maxData = (int) ini_get(self::XDEBUG_KEY_MAX_DATA);
    $maxDepth = (int) ini_get(self::XDEBUG_KEY_MAX_DEPTH);

    // We force test values and we create an array to test those values
    $arrayToTest = $this->getDumpTestArray();

    // We test our function
    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . (self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1) . self::DUMP_BEGIN_THIRD . str_repeat('0', self::XDEBUG_TEST_VALUE_MAX_DATA + 1) . "\"\n"
      . $this->getExpectedOutputPartial(self::XDEBUG_TEST_VALUE_MAX_CHILDREN)
      . "\n  (more elements)...\n}\n"
    );

    dump(
      [false, true, false],
      $arrayToTest
    );

    // We restore the values
    ini_set(self::XDEBUG_KEY_MAX_CHILDREN, strval($maxChildren));
    ini_set(self::XDEBUG_KEY_MAX_DATA, strval($maxData));
    ini_set(self::XDEBUG_KEY_MAX_DEPTH, strval($maxDepth));
  }

  public function testDump_MaxChildrenTrueMaxDataTrueMaxDepthFalse() : void
  {
    $maxChildren = (int) ini_get(self::XDEBUG_KEY_MAX_CHILDREN);
    $maxData = (int) ini_get(self::XDEBUG_KEY_MAX_DATA);
    $maxDepth = (int) ini_get(self::XDEBUG_KEY_MAX_DEPTH);
    $arrayToTest = $this->getDumpTestArray();

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . (self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1) . self::DUMP_BEGIN_THIRD . str_repeat('0', self::XDEBUG_TEST_VALUE_MAX_DATA + 1) . "\"\n"
      . $this->getExpectedOutputPartial(self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1)
      . "}\n"
    );

    dump(
      [true, true, false],
      $arrayToTest
    );

    ini_set(self::XDEBUG_KEY_MAX_CHILDREN, strval($maxChildren));
    ini_set(self::XDEBUG_KEY_MAX_DATA, strval($maxData));
    ini_set(self::XDEBUG_KEY_MAX_DEPTH, strval($maxDepth));
  }

  public function testDump_MaxChildrenTrueMaxDataTrueMaxDepthTrue() : void
  {
    $maxData = (int) ini_get(self::XDEBUG_KEY_MAX_DATA);
    $maxChildren = (int) ini_get(self::XDEBUG_KEY_MAX_CHILDREN);
    $maxDepth = (int) ini_get(self::XDEBUG_KEY_MAX_DEPTH);
    $arrayToTest = $this->getDumpTestArray();

    $this->expectOutputString(
      self::DUMP_STRING . (__LINE__ + 7) . self::DUMP_STRING_SECOND
      . "\narray(" . (self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1) . self::DUMP_BEGIN_THIRD . str_repeat('0', self::XDEBUG_TEST_VALUE_MAX_DATA + 1) . "\"\n"
      . $this->getExpectedOutputPartial(self::XDEBUG_TEST_VALUE_MAX_CHILDREN + 1, true)
      . "}\n"
    );

    dump(
      [true, true, true],
      $arrayToTest
    );

    ini_set(self::XDEBUG_KEY_MAX_CHILDREN, strval($maxChildren));
    ini_set(self::XDEBUG_KEY_MAX_DATA, strval($maxData));
    ini_set(self::XDEBUG_KEY_MAX_DEPTH, strval($maxDepth));
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
