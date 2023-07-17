<?php
declare(strict_types=1);

namespace src\console;

use otra\{console\OtraExceptionCli, OtraException};
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV,PROD};

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionCliTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
  }

  /**
   * @author Lionel Péramo
   */
  public function testOtraExceptionCli(): void
  {
    self::assertInstanceOf(
      OtraExceptionCli::class,
      new OtraExceptionCli(new OtraException('test'))
    );
  }

  /**
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testOtraExceptionCli_WithContext(): void
  {
    self::assertInstanceOf(
      OtraExceptionCli::class,
      new OtraExceptionCli(
        new OtraException(
          'test',
          NULL,
          '',
          NULL,
          ['variables' => []],
        )
      )
    );
  }
}
