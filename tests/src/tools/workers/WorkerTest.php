<?php
declare(strict_types=1);

namespace src\tools\workers;

use otra\tools\workers\{Worker};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WorkerTest extends TestCase
{
  private const
    COMMAND = 'sleep',
    SUCCESS_MESSAGE = 'hello',
    VERBOSE = 0,
    TIMEOUT = 120;

  /**
   * @throws \ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testWorker(): void
  {
    // launching
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::VERBOSE,
      self::TIMEOUT
    );

    // testing
    self::assertInstanceOf(Worker::class, $worker);

    self::assertIsString($worker->command);
    self::assertEquals(self::COMMAND, $worker->command);

    self::assertIsInt($worker->verbose);
    self::assertEquals(self::VERBOSE, $worker->verbose);

    $workerSuccessMessage = removeFieldScopeProtection(Worker::class, 'successMessage')->getValue($worker);
    self::assertIsString($workerSuccessMessage);
    self::assertEquals(self::SUCCESS_MESSAGE, $workerSuccessMessage);

    $workerTimeout = removeFieldScopeProtection(Worker::class, 'timeout')->getValue($worker);
    self::assertIsInt($workerTimeout);
    self::assertEquals(self::TIMEOUT, $workerTimeout);
  }

  /**
   * @depends testWorker
   *
   * @author Lionel Péramo
   */
  public function testDone(): void
  {
    // launching
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, 0);
    $string = $worker->done('Worker command done.');

    // testing
    self::assertIsString($string);
    self::assertEquals('Worker command done.' . CLI_GREEN . "\e[15;2]" . self::SUCCESS_MESSAGE . END_COLOR, $string);
  }

  /**
   * @depends testWorker
   *
   * @author Lionel Péramo
   */
  public function testFail(): void
  {
    // context
    define('TEST_STDOUT', 'Worker command failed.');
    define('TEST_STDERR', 'my error.');
    define('TEST_STATUS', 'the status');

    // launching
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, 0);
    $string = $worker->fail(TEST_STDOUT, TEST_STDERR, TEST_STATUS);

    // testing
    self::assertIsString($string);
    self::assertEquals(
      CLI_RED . 'Fail! The command was : "' . self::COMMAND . '"' . END_COLOR . PHP_EOL .
      'STDOUT : ' . TEST_STDOUT . PHP_EOL .
      'STDERR : ' . TEST_STDERR . PHP_EOL .
      'STATUS : ' . TEST_STATUS,
      $string
    );
  }
}
