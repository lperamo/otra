<?php

use lib\myLibs\OtraException;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  /**
   * @throws ReflectionException
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testOtraException(): void
  {
    $exception = new OtraException('test');
    $this->assertInstanceOf(OtraException::class, $exception);
    removeMethodScopeProtection(OtraException::class, 'errorMessage')
      ->invokeArgs($exception, []);
  }

  /**
   * @throws ReflectionException
   * @throws OtraException
   * @author Lionel Péramo
   */
  public function testOtraException_WithContext(): void
  {
    $exception = new OtraException('test');

    /* We cannot force the PHP_SAPI constant so it will launch OtraExceptionCLI but we can workaround it.
     * We launch it this way anyway but we manually set the context after in order to not be overwritten by the
     * OtraExceptionCLI class. */
    $exception->context = ['variables' => []];

    $this->assertInstanceOf(OtraException::class, $exception);
    removeMethodScopeProtection(OtraException::class, 'errorMessage')
      ->invokeArgs($exception, []);
  }
}
