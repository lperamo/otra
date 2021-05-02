<?php
declare(strict_types=1);

namespace src\tools\debug;

use phpunit\framework\TestCase;
use function otra\tools\delTree;

/**
 * @runTestsInSeparateProcesses
 */
class DumpTest extends TestCase
{
  private const
    LOG_PATH = BASE_PATH . 'logs/',
    DUMP_STRING = 'OTRA DUMP - ' . __FILE__ . ':',
    OTRA_DEBUG_TEST_VALUE_MAX_CHILDREN = 5,
    OTRA_DEBUG_TEST_VALUE_MAX_DATA = 10,
    OTRA_DEBUG_TEST_VALUE_MAX_DEPTH = 3;

  private static string $LOGS_PROD_PATH;
  private static bool $outputFlag = true;
  // fixes issues like in 'testDump_NoParameters' test, AllConfig is not loaded without that line
  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = PROD;
    self::$LOGS_PROD_PATH = self::LOG_PATH . $_SERVER[APP_ENV];

    require TEST_PATH . 'config/AllConfigGood.php';
    require CORE_PATH . 'tools/getSourceFromFile.php';
    require CORE_PATH . 'tools/debug/dump.php';

    if (file_exists(self::$LOGS_PROD_PATH) === false)
      mkdir(self::$LOGS_PROD_PATH, 0777, true);
  }

  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();

    if (OTRA_PROJECT === false)
    {
      if (file_exists(self::LOG_PATH))
        delTree(self::LOG_PATH);
    }
  }

  /**
   * Updates the depth of the array. For a depth of 3, we will see 3 levels of KEYS (2 levels only of values).
   *
   * @param int   $depthIndex
   * @param array $array
   */
  private static function fillArrayDepth(int &$depthIndex, array &$array)
  {
    while($depthIndex < self::OTRA_DEBUG_TEST_VALUE_MAX_DEPTH - 1)
    {
      $array[0] = [0 => ''];
      ++$depthIndex;
      self::fillArrayDepth($depthIndex, $array[0]);
    }
  }

  /**
   * Force test values and returns an array to test those values
   *
   * @return array<int, array>
   * @author Lionel Péramo
   */
  private static function getDumpTestArray() : array
  {
    // for children test
    $arrayToTest = array_fill(0, self::OTRA_DEBUG_TEST_VALUE_MAX_CHILDREN + 1, 0);

    // for data length test
    $arrayToTest[0] = str_repeat('0', self::OTRA_DEBUG_TEST_VALUE_MAX_DATA + 1);

    // for depth test
    $arrayToTest[1] = [0 => ''];
    $depthIndex = 0;

    self::fillArrayDepth($depthIndex, $arrayToTest[1]);

    return $arrayToTest;
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
    while ($depth < self::OTRA_DEBUG_TEST_VALUE_MAX_DEPTH)
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
   * @author Lionel Péramo
   */
  public function testDump_NoParameters() : void
  {
    // testing
    $this->expectOutputString(
      CLI_TABLE . 'OTRA DUMP - ' . __FILE__ . ':' . (__LINE__ + 5) . END_COLOR . PHP_EOL . PHP_EOL .
      getSourceFromFileCli(__FILE__, __LINE__ + 4) . PHP_EOL
    );

    // launching
    dump();
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxChildrenFalseMaxDataFalseMaxDepthFalse() : void
  {
    // We test our function
    $this->expectOutputString(
      CLI_TABLE . self::DUMP_STRING . (__LINE__ + 8) . END_COLOR . PHP_EOL . PHP_EOL .
      getSourceFromFileCli(__FILE__, __LINE__ + 7) . PHP_EOL .
      '0 => array (6) ' . PHP_EOL . END_COLOR .
      '0 => string (11) \'\'' . ADD_BOLD . '(cut)' . REMOVE_BOLD_INTENSITY . PHP_EOL .
      '...' . PHP_EOL
    );

    // launching
    paramDump([0, 0, 0], self::getDumpTestArray());
  }

  /**
   * @author Lionel Péramo
   */
  public function testDump_MaxChildrenFalseMaxDataTrueMaxDepthFalse() : void
  {
    // We test our function
    $this->expectOutputString(
      CLI_TABLE . self::DUMP_STRING . (__LINE__ + 8) . END_COLOR . PHP_EOL . PHP_EOL .
      getSourceFromFileCli(__FILE__, __LINE__ + 7) . PHP_EOL .
      '0 => array (6) ' . PHP_EOL . END_COLOR .
      '0 => string (11) \'00000000000\'' . PHP_EOL .
      '...' . PHP_EOL
    );

    // launching
    paramDump([0, -1, 0], self::getDumpTestArray());
  }

  public function testDump_MaxChildrenTrueMaxDataTrueMaxDepthFalse() : void
  {
    // We test our function
    $this->expectOutputString(
      CLI_TABLE . self::DUMP_STRING . (__LINE__ + 13) . END_COLOR . PHP_EOL . PHP_EOL .
      getSourceFromFileCli(__FILE__, __LINE__ + 12) . PHP_EOL .
      '0 => array (6) ' . PHP_EOL .
      END_COLOR . '0 => string (11) \'00000000000\'' . PHP_EOL .
      END_COLOR . '1 => ' . PHP_EOL .
      END_COLOR . ADD_BOLD . '...' . REMOVE_BOLD_INTENSITY . PHP_EOL .
      END_COLOR . '2 => 0' . PHP_EOL .
      END_COLOR . '3 => 0' . PHP_EOL .
      END_COLOR . '4 => 0' . PHP_EOL .
      END_COLOR . '5 => 0' . PHP_EOL
    );

    // launching
    paramDump([-1, -1, 0], self::getDumpTestArray());
  }

  public function testDump_MaxChildrenTrueMaxDataTrueMaxDepthTrue() : void
  {
    // We test our function
    $this->expectOutputString(
      CLI_TABLE . self::DUMP_STRING . (__LINE__ + 16) . END_COLOR . PHP_EOL . PHP_EOL .
      getSourceFromFileCli(__FILE__, __LINE__ + 15) . PHP_EOL .
      '0 => array (6) ' . PHP_EOL .
      END_COLOR . '0 => string (11) \'00000000000\'' . PHP_EOL .
      END_COLOR . '1 => array (1) ' . PHP_EOL .
      ADD_BOLD . CLI_INFO . '│ ' . END_COLOR . '0 => array (1) ' . PHP_EOL .
      ADD_BOLD . CLI_INFO . '│ ' . ADD_BOLD . CLI_ERROR . '│ ' . END_COLOR . '0 => array (1) ' . PHP_EOL .
      ADD_BOLD . CLI_INFO . '│ ' . ADD_BOLD . CLI_ERROR . '│ ' . CLI_SUCCESS . '│ ' .  END_COLOR .
      '0 => string (0) \'\'' . PHP_EOL .
      END_COLOR . '2 => 0' . PHP_EOL .
      END_COLOR . '3 => 0' . PHP_EOL .
      END_COLOR . '4 => 0' . PHP_EOL .
      END_COLOR . '5 => 0' . PHP_EOL
    );

    // launching
    paramDump([-1, -1, -1], self::getDumpTestArray());
  }
}
