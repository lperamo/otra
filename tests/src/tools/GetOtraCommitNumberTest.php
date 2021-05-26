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
  private const GET_OTRA_COMMIT_NUMBER_PATH = CORE_PATH . 'tools/getOtraCommitNumber.php';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testShortAndConsole() : void
  {
    // context
    require self::GET_OTRA_COMMIT_NUMBER_PATH;

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{7}$@',
      getOtraCommitNumber(true, true),
      'Testing short version of the commit for the console'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testShortAndWeb() : void
  {
    // context
    require self::GET_OTRA_COMMIT_NUMBER_PATH;

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{7}$@',
      getOtraCommitNumber(false, true),
      'Testing short version of the commit for the web'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testLongAndWeb() : void
  {
    // context
    require self::GET_OTRA_COMMIT_NUMBER_PATH;

    // testing
    self::assertMatchesRegularExpression(
      '@^\\w{40}$@',
      getOtraCommitNumber(),
      'Testing long version of the commit for the web'
    );
  }
}
