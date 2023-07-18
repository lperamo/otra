<?php
declare(strict_types=1);

namespace src\tools\workers;

use ReflectionClass;
use ReflectionException;
use otra\tools\workers\{Worker,WorkerManager};
use PHPUnit\Framework\TestCase;
use const otra\console\
{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class WorkerManagerTest extends TestCase
{
  private const
    COMMAND = 'sleep 0.001',
    COMMAND_SLEEP_2 = 'sleep 2',
    BAD_COMMAND = 'slept',
    SUCCESS_MESSAGE = 'hello',
    SUCCESS_MESSAGE_2 = 'hi',
    SUCCESS_MESSAGE_3 = 'hi how are you?' . PHP_EOL . 'I\'m fine and you?',
    SUCCESS_MESSAGE_4 = 'success message 4' . PHP_EOL . 'end',
    FAIL_MESSAGE = CLI_ERROR . 'Fail! ' . END_COLOR . PHP_EOL .
      'STDOUT : ' . PHP_EOL .
      'STDERR : sh: 1: slept: not found' . PHP_EOL .
      PHP_EOL .
      'Exit code : 127',
    WAITING_MESSAGE = 'waiting for the result of the first final message ...',
    WAITING_MESSAGE_2 = 'waiting for the result of the second final message ...',
    WAITING_MESSAGE_3 = 'waiting for the result of the third final message ...',
    WAITING_MESSAGE_4 = 'waiting for the result of the fourth final message ...',
    VERBOSE = 2,
    CUSTOM_TIMEOUT = 1,
    OTRA_FIELD_STDIN_STREAMS = 'stdinStreams',
    OTRA_FIELD_STDOUT_STREAMS = 'stdoutStreams',
    OTRA_FIELD_STDERR_STREAMS = 'stderrStreams',
    UP_ONE_LINE = "\033[1A",
    CLEAR_PREVIOUS_LINE = self::UP_ONE_LINE . WorkerManager::ERASE_TO_END_OF_LINE,
    WHITE = "\e[15;2]",
    STREAMS_DESTRUCT_MESSAGE = 'streams must be empty after the Worker Manager destruction.',
    TIMEOUT = 60;

  /**
   * @param string $command
   *
   * @throws ReflectionException
   */
  public static function experimentDetach(string $command): void {
    // context
    $workerManager = new WorkerManager();

    $worker = new Worker(
      $command,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    define(__NAMESPACE__ . '\\TEST_DETACH_STATUS_SUCCESS', 0);
    define(__NAMESPACE__ . '\\TEST_DETACH_STATUS_WAS_RUNNING', true);

    // launching
    $foundKey = array_search($worker, $workerManager::$workers, true);
    $status = $workerManager->detach($worker);

    // testing workers
    self::assertArrayNotHasKey(
      $foundKey,
      $workerManager::$workers,
      'A detached worker must no be present in the Worker Manager afterwards.'
    );

    $reflectedClass = new ReflectionClass(WorkerManager::class);
    // testing processes
    self::assertArrayNotHasKey(
      $foundKey,
      $reflectedClass->getProperty('processes')->getValue($workerManager),
      'The process related to the detached worker must no be present in the the Worker Manager after that the worker has been detached.'
    );

    // testing streams
    self::assertArrayNotHasKey(
      $foundKey,
      $reflectedClass->getProperty(self::OTRA_FIELD_STDIN_STREAMS)->getValue($workerManager),
      'Stdin streams must be empty after we detached a worker.'
    );

    self::assertArrayNotHasKey(
      $foundKey,
      $reflectedClass->getProperty(self::OTRA_FIELD_STDOUT_STREAMS)->getValue($workerManager),
      'Stdout streams must be empty after we detached a worker.'
    );

    self::assertArrayNotHasKey(
      $foundKey,
      $reflectedClass->getProperty(self::OTRA_FIELD_STDERR_STREAMS)->getValue($workerManager),
      'Stderr streams must be empty after we detached a worker.'
    );

    // detachment successful
    self::assertIsArray($status);
    self::assertSame(
      $status[0]
        ? TEST_DETACH_STATUS_WAS_RUNNING
        : TEST_DETACH_STATUS_SUCCESS,
      $status[1],
      'Wrong status' . PHP_EOL . print_r($status, true)
    );

    // cleaning
    unset($workerManager);
  }

  /**
   * @author Lionel Péramo
   */
  public function testConstruct() : void
  {
    // launching
    $manager = new WorkerManager();

    // testing
    self::assertInstanceOf(WorkerManager::class, $manager, 'Checking the type of Worker Manager.');
  }

  /**
   * @depends testConstruct
   *
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testDestruct() : void
  {
    // launching
    $workerManager = new WorkerManager();
    $workerManager->__destruct();

    // testing streams
    $reflectedClass = new ReflectionClass(WorkerManager::class);
    self::assertEmpty(
      $reflectedClass->getProperty(self::OTRA_FIELD_STDIN_STREAMS)->getValue($workerManager),
      'Stdin' . self::STREAMS_DESTRUCT_MESSAGE
    );
    self::assertEmpty(
      $reflectedClass->getProperty(self::OTRA_FIELD_STDOUT_STREAMS)->getValue($workerManager),
      'Stdout' . self::STREAMS_DESTRUCT_MESSAGE
    );
    self::assertEmpty(
      $reflectedClass->getProperty(self::OTRA_FIELD_STDERR_STREAMS)->getValue($workerManager),
      'Stderr' . self::STREAMS_DESTRUCT_MESSAGE
    );
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   *
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testAttach() : void
  {
    // context
    $workerManager = new WorkerManager();
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE
    );
    define(__NAMESPACE__ . '\\TEST_STREAM_NON_BLOCKING_MODE', false);

    // launching
    $workerManager->attach($worker);

    // 1. testing streams
    $reflectedClass = new ReflectionClass(WorkerManager::class);
    $stdinStreams = $reflectedClass->getProperty(self::OTRA_FIELD_STDIN_STREAMS)->getValue($workerManager);
    self::assertNotEmpty($stdinStreams, 'Stdin streams must not be empty after we attached a worker.');

    $stdoutStreams = $reflectedClass->getProperty(self::OTRA_FIELD_STDOUT_STREAMS)->getValue($workerManager);
    self::assertNotEmpty($stdoutStreams, 'Stdout streams must not be empty after we attached a worker.');

    foreach ($stdoutStreams as $stdoutStream)
    {
      self::assertSame(
        TEST_STREAM_NON_BLOCKING_MODE,
        stream_get_meta_data($stdoutStream)['blocked'],
        'We must have a non blocking mode for the streams.'
      );
    }

    $stderrStreams = $reflectedClass->getProperty(self::OTRA_FIELD_STDERR_STREAMS)->getValue($workerManager);
    self::assertNotEmpty($stderrStreams, 'Stderr streams must not be empty after we attached a worker.');

    // 2. testing workers
    self::assertCount(
      1,
      $workerManager::$workers,
      'There must be only one worker attached after having attached one worker on a empty Worker Manager.'
    );
    self::assertContainsOnly(
      Worker::class,
      $workerManager::$workers,
      false,
      'Worker Manager must only contains Worker instances.'
    );

    // 3. testing processes
    self::assertNotEmpty(
      $reflectedClass->getProperty('processes'),
      'There must be processes when we have attached a worker to the Worker Manager.'
    );

    // cleaning
    $workerManager->__destruct();
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   *
   * @throws ReflectionException
   */
  public function testDetach() : void
  {
    self::experimentDetach(self::COMMAND);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   *
   * @throws ReflectionException
   */
  public function testDetachLongProcess() : void
  {
    self::experimentDetach(self::COMMAND_SLEEP_2);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   */
  public function testListen_OneWorker() : void
  {
    // Context
    $workerManager = new WorkerManager();
    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    // Launching
    $workerManager->listen();

    // Testing
    $this->expectOutputString(
      self::WAITING_MESSAGE . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE .
      self::WHITE . self::SUCCESS_MESSAGE . ' ' . $worker->command .
      PHP_EOL
    );

    // normally, the worker once terminated has been detached in the listen() method but in case there was an exception
    // we ensure that there is no remaining working processes
    if (count(WorkerManager::$workers) > 0)
      $workerManager->detach($worker);

    // Cleaning
    unset($workerManager);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   */
  public function testListen_OneWorkerTooBig() : void
  {
    // Context
    $worker = new Worker(
      self::COMMAND_SLEEP_2,
      self::SUCCESS_MESSAGE_2,
      self::WAITING_MESSAGE_2,
      null,
      self::VERBOSE,
      self::CUSTOM_TIMEOUT
    );
    $workerManager = new WorkerManager();
    $workerManager->attach($worker);
    // Launching
    $workerManager->listen();

    // Testing
    $this->expectOutputString(
      self::WAITING_MESSAGE_2 . PHP_EOL .
      CLI_ERROR . 'The process that launched ' . CLI_INFO_HIGHLIGHT . self::COMMAND_SLEEP_2 . CLI_ERROR .
      ' was hanging during ' . self::CUSTOM_TIMEOUT . ' second. We will kill the process.' . END_COLOR . PHP_EOL
    );

    // normally, the worker once terminated has been detached in the listen() method but in case there was an exception
    // we ensure that there is no remaining working processes
    if (count(WorkerManager::$workers) > 0)
      $workerManager->detach($worker);

    // Cleaning
    unset($workerManager);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   * @depends testDetachLongProcess
   */
  public function testListen_SomeWorkers() : void
  {
    // Context
    $workerManager = new WorkerManager();

    $worker = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    $workerBis = new Worker(
      self::COMMAND_SLEEP_2,
      self::SUCCESS_MESSAGE_2,
      self::WAITING_MESSAGE_2,
      null,
      self::VERBOSE
    );
    $workerManager->attach($workerBis);

    // Launching
    while (0 < count($workerManager::$workers))
      $workerManager->listen();

    // Testing
    $messageStart = self::WHITE;
    $firstMessageEnd = ' ' . self::COMMAND . PHP_EOL;

    $this->expectOutputString(
      self::WAITING_MESSAGE . PHP_EOL .
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE .
      self::CLEAR_PREVIOUS_LINE .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE .
      self::CLEAR_PREVIOUS_LINE .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL
    );

    // Cleaning
    unset($workerManager);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   */
  public function testListen_orderedOutput() : void
  {
    // Context
    $workerManager = new WorkerManager();

    $worker = new Worker(
      self::COMMAND_SLEEP_2,
      self::SUCCESS_MESSAGE_2,
      self::WAITING_MESSAGE_2,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    $workerBis = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE_3,
      self::WAITING_MESSAGE_3,
      null,
      self::VERBOSE
    );
    $workerManager->attach($workerBis);

    // Launching
    while (0 < count($workerManager::$workers))
      $workerManager->listen();

    // Testing
    $messageStart = self::WHITE;
    $firstMessageEnd = ' ' . self::COMMAND . PHP_EOL;

    $this->expectOutputString(
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::WAITING_MESSAGE_3 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .
      self::WAITING_MESSAGE_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE_3 . $firstMessageEnd .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .
      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE_3 . ' ' . self::COMMAND . PHP_EOL
    );

    // Cleaning
    unset($workerManager);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   */
  public function testSubworkers() : void
  {
    // Context
    $workerManager = new WorkerManager();

    $worker = new Worker(
      self::COMMAND_SLEEP_2,
      self::SUCCESS_MESSAGE_2,
      self::WAITING_MESSAGE_2,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    $workerBis = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE,
      self::TIMEOUT,
      [
        new Worker(
          self::COMMAND_SLEEP_2,
          self::SUCCESS_MESSAGE_3,
          self::WAITING_MESSAGE_3,
          null,
          self::VERBOSE
        ),
        new Worker(
          self::COMMAND,
          self::SUCCESS_MESSAGE_4,
          self::WAITING_MESSAGE_4,
          null,
          self::VERBOSE
        )
      ]
    );
    $workerManager->attach($workerBis);

    // Launching
    while (0 < count($workerManager::$workers))
      $workerManager->listen();

    // Testing
    $messageStart = self::WHITE;
    $firstMessageEnd = ' ' . self::COMMAND . PHP_EOL;

    $this->expectOutputString(
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::WAITING_MESSAGE . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .

      self::WAITING_MESSAGE_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      self::WAITING_MESSAGE_3 . PHP_EOL .
      self::WAITING_MESSAGE_4 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .

      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      self::WAITING_MESSAGE_3 . PHP_EOL .
      self::WAITING_MESSAGE_4 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .

      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      self::WAITING_MESSAGE_3 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE_4 . ' ' . self::COMMAND . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .
      self::CLEAR_PREVIOUS_LINE .

      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      $messageStart . self::SUCCESS_MESSAGE_3 . ' ' . self::COMMAND_SLEEP_2 . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE_4 . ' ' . self::COMMAND . PHP_EOL
    );

    // Cleaning
    unset($workerManager);
  }

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @depends testDetach
   */
  public function testFailures() : void
  {
    // Context
    $workerManager = new WorkerManager();

    $worker = new Worker(
      self::BAD_COMMAND,
      self::SUCCESS_MESSAGE,
      self::WAITING_MESSAGE,
      null,
      self::VERBOSE
    );
    $workerManager->attach($worker);

    $workerBis = new Worker(
      self::COMMAND,
      self::SUCCESS_MESSAGE_2,
      self::WAITING_MESSAGE_2,
      null,
      self::VERBOSE
    );
    $workerManager->attach($workerBis);

    // Launching
    while (0 < count($workerManager::$workers))
      $workerManager->listen();

    // Testing
    $messageStart = self::WHITE;

    $this->expectOutputString(
      self::WAITING_MESSAGE . PHP_EOL .
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .

      self::BAD_COMMAND . PHP_EOL .
      self::WAITING_MESSAGE_2 . PHP_EOL .
      self::FAIL_MESSAGE . PHP_EOL .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .
      self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE . self::CLEAR_PREVIOUS_LINE .

      self::BAD_COMMAND . PHP_EOL .
      $messageStart . self::SUCCESS_MESSAGE_2 . ' ' . self::COMMAND . PHP_EOL .
      self::FAIL_MESSAGE . PHP_EOL
    );

    // Cleaning
    unset($workerManager);
  }
}
