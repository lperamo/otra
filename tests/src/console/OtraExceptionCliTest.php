<?php
declare(strict_types=1);

use otra\{console\OtraExceptionCLI, OtraException};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionCliTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  /**
   * @author Lionel Péramo
   */
  public function testOtraExceptionCli(): void
  {
    $this->assertInstanceOf(
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
    $this->assertInstanceOf(
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
