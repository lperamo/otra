<?php
declare(strict_types=1);

namespace src\tools;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\CORE_PATH;
use function otra\tools\getOtraCommitNumber;

/**
 * @runTestsInSeparateProcesses
 */
class GetOtraCommitNumberTest extends TestCase
{
  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testShortAndConsole() : void
  {
    // context
    require CORE_PATH . 'tools/getOtraCommitNumber.php';

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{7}$@',
      getOtraCommitNumber(true, true),
      'Testing short version of the commit'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testShortAndWeb() : void
  {
    // context
    require CORE_PATH . 'tools/getOtraCommitNumber.php';

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{7}$@',
      getOtraCommitNumber(false, true),
      'Testing short version of the commit'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testLongAndWeb() : void
  {
    // context
    require CORE_PATH . 'tools/getOtraCommitNumber.php';

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{40}$@',
      getOtraCommitNumber(),
      'Testing short version of the commit'
    );
  }
}
