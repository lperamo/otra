<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\processReturn;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ProcessReturnTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testCaseRoutesMainConfig(): void
  {
    // context
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);

    $inclusionCode = PHP_EOL . 'require BUNDLES_PATH . \'config/Routes.php\'' . PHP_EOL . ');';
    $includingStartCode = 'self::$allRoutes = array_merge(' . PHP_EOL . 'self::$allRoutes,';
    $includingCode = $includingStartCode . $inclusionCode;

    // PHP ending tag is added dynamically by TaskFileOperation::assembleFiles() to ensure all files concatenate
    // correctly
    $includedCode = '<?php declare(strict_types=1);return [\'testKey\'=>\'testValue\'];' . PHP_EOL . '?>';

    // launching (actually it works only with 'str_pos' not 'mb_strpos'!) see TaskFileOperation:914 (can change) in
    // getFileInfoFromRequiresAndExtends()
    processReturn(
      $includingCode,
      $includedCode,
      $inclusionCode,
      strpos($includingCode, $inclusionCode)
    );

    // testing
    $insertedCode = '[\'testKey\'=>\'testValue\']';
    self::assertSame(
      $includingStartCode . $insertedCode . PHP_EOL . ');',
      $includingCode,
      'Testing $includingCode...'
    );
    self::assertSame(
      $insertedCode,
      $includedCode,
      'Testing $includedCode...'
    );
  }
}
