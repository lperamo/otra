<?php
declare(strict_types=1);

namespace src\console;

use otra\{console\OtraExceptionCli, OtraException};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionCliTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
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
