<?php
declare(strict_types=1);

namespace src\tools\workers;

use otra\tools\workers\{Worker};
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_ERROR, END_COLOR};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class WorkerTest extends TestCase
{
  private const
    COMMAND = 'sleep',
    SUCCESS_MESSAGE = 'hello',
    WAITING_MESSAGE = 'waiting for the final message',
    FAIL_MESSAGE = 'fail',
    VERBOSE = 0,
    TIMEOUT = 120,
    WHITE = "\e[15;2]";

  /**
   * @author Lionel Péramo
   */
  public function testWorker(): void
  {
    // launching
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE,
      self::TIMEOUT
    );

    // testing
    self::assertInstanceOf(Worker::class, $worker);

    self::assertIsString($worker->command);
    self::assertSame(self::COMMAND, $worker->command);

    self::assertIsInt($worker->verbose);
    self::assertSame(self::VERBOSE, $worker->verbose);

    self::assertIsString($worker->successMessage);
    self::assertSame(self::SUCCESS_MESSAGE, $worker->successMessage);

    self::assertIsString($worker->waitingMessage);
    self::assertSame(self::WAITING_MESSAGE, $worker->waitingMessage);

    self::assertIsString($worker->waitingMessage);
    self::assertNull($worker->failMessage);

    self::assertIsInt($worker->timeout);
    self::assertSame(self::TIMEOUT, $worker->timeout);
  }

  /**
   * @depends testWorker
   *
   * @author Lionel Péramo
   */
  public function testDone(): void
  {
    // launching
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      0
    );
    $string = $worker->done('Worker command done.');

    // testing
    self::assertIsString($string);
    self::assertSame('Worker command done.' . self::WHITE . self::SUCCESS_MESSAGE, $string);
  }

  /**
   * @depends testWorker
   *
   * @author Lionel Péramo
   */
  public function testFail(): void
  {
    // context
    define(__NAMESPACE__ . '\\TEST_STDOUT', 'Worker command failed.');
    define(__NAMESPACE__ . '\\TEST_STDERR', 'my error.');
    define(__NAMESPACE__ . '\\TEST_STATUS', -1);

    // launching
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      0
    );
    $string = $worker->fail(TEST_STDOUT, TEST_STDERR, TEST_STATUS);

    // testing
    self::assertIsString($string);
    self::assertSame(
      CLI_ERROR . 'Fail! ' . END_COLOR . PHP_EOL .
      'STDOUT : ' . TEST_STDOUT . PHP_EOL .
      'STDERR : ' . TEST_STDERR . PHP_EOL .
      'Exit code : ' . TEST_STATUS,
      $string
    );
  }

  /**
   * @depends testWorker
   *
   * @author Lionel Péramo
   */
  public function testFail_customMessage(): void
  {
    // context
    define(__NAMESPACE__ . '\\TEST_STDOUT', 'Worker command failed.');
    define(__NAMESPACE__ . '\\TEST_STDERR', 'my error.');
    define(__NAMESPACE__ . '\\TEST_STATUS', -1);

    // launching
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      self::FAIL_MESSAGE,
      0
    );
    $string = $worker->fail(TEST_STDOUT, TEST_STDERR, TEST_STATUS);

    // testing
    self::assertIsString($string);
    self::assertSame(self::FAIL_MESSAGE, $string);
  }
}
