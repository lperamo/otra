<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\hasSyntaxErrors;

/**
 * @runTestsInSeparateProcesses
 */
class HasSyntaxErrorsTest extends TestCase
{
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testError()
  {
    // launching
    $hasSyntaxErrors = hasSyntaxErrors(TEST_PATH . 'examples/deployment/withSyntaxErrors.php');

    // testing
    static::assertTrue($hasSyntaxErrors);
  }

  /**
   * @author Lionel Péramo
   */
  public function testNoErrors()
  {
    // launching
    $hasSyntaxErrors = hasSyntaxErrors(TEST_PATH . 'examples/deployment/noSyntaxErrors.php');

    // testing
    static::assertFalse($hasSyntaxErrors);
  }
}
