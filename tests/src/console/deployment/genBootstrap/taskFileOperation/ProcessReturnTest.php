<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use phpunit\framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\processReturn;

/**
 * @runTestsInSeparateProcesses
 */
class ProcessReturnTest extends TestCase
{
  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   */
  public function testCaseRoutesMainConfig()
  {
    // context
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);

    $inclusionCode = PHP_EOL . 'require BUNDLES_PATH . \'config/Routes.php\'' . PHP_EOL . ');';
    $includingStartCode = 'self::$allRoutes = array_merge(' . PHP_EOL . 'self::$allRoutes,';
    $includingCode = $includingStartCode . $inclusionCode;

    // PHP ending tag is added dynamically by TaskFileOperation::assembleFiles() to ensure all files concatenate
    // correctly
    $includedCode = '<?php declare(strict_types=1);return [\'testKey\'=>\'testValue\'];' . PHP_EOL . '?>';

    // launching
    processReturn(
      $includingCode,
      $includedCode,
      $inclusionCode,
      mb_strpos($includingCode, $inclusionCode)
    );

    // testing
    $insertedCode = '[\'testKey\'=>\'testValue\']';
    self::assertEquals(
      $includingStartCode . $insertedCode . PHP_EOL . ');',
      $includingCode,
      'Testing $includingCode...'
    );
    self::assertEquals(
      $insertedCode,
      $includedCode,
      'Testing $includedCode...'
    );
  }
}
