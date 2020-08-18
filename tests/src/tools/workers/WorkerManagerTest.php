<?php
declare(strict_types=1);

namespace src\tools\workers;

use ReflectionException;
use otra\tools\workers\{Worker,WorkerManager};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WorkerManagerTest extends TestCase
{
  private const
    COMMAND = 'sleep 0.001',
    COMMAND_2 = 'sleep 2',
    SUCCESS_MESSAGE = 'hello',
    SUCCESS_MESSAGE_2 = 'hi',
    VERBOSE = 2,
    OTRA_FIELD_STDIN_STREAMS = 'stdinStreams',
    OTRA_FIELD_STDOUT_STREAMS = 'stdoutStreams',
    OTRA_FIELD_STDERR_STREAMS = 'stderrStreams';

  /**
   * @param string $command
   *
   * @throws ReflectionException
   */
  public static function experimentDetach(string $command) {
    // context
    $worker = new Worker($command, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager = new WorkerManager();
    $workerManager->attach($worker);
    define('TEST_DETACH_STATUS_SUCCESS', 0);
    define('TEST_DETACH_STATUS_WAS_RUNNING', true);

    // launching
    $foundKey = array_search($worker, $workerManager::$workers, true);
    $status = $workerManager->detach($worker);

    // testing workers
    self::assertArrayNotHasKey(
      $foundKey,
      $workerManager::$workers,
      'A detached worker must no be present in the Worker Manager afterwards.'
    );

    // testing processes
    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, 'processes')->getValue($workerManager),
      'The process related to the detached worker must no be present in the the Worker Manager after that the worker has been detached.'
    );

    // testing streams
    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDIN_STREAMS)->getValue($workerManager),
      'Stdin streams must be empty after we detached a worker.'
    );

    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDOUT_STREAMS)->getValue($workerManager),
      'Stdout streams must be empty after we detached a worker.'
    );

    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDERR_STREAMS)->getValue($workerManager),
      'Stderr streams must be empty after we detached a worker.'
    );

    // detachment successful
    self::assertIsArray($status);
    self::assertEquals(
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
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDIN_STREAMS)
      ->getValue($workerManager),
      'Stdin streams must be empty after the Worker Manager destruction.'
    );
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDOUT_STREAMS)
        ->getValue($workerManager),
      'Stdout streams must be empty after the Worker Manager destruction.'
    );
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDERR_STREAMS)
        ->getValue($workerManager),
      'Stderr streams must be empty after the Worker Manager destruction.'
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
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager  = new WorkerManager();
    define('TEST_STREAM_NON_BLOCKING_MODE', false);

    // launching
    $workerManager->attach($worker);

    // 1. testing streams
    $stdinStreams = removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDIN_STREAMS)
      ->getValue($workerManager);
    self::assertNotEmpty($stdinStreams, 'Stdin streams must not be empty after we attached a worker.');

    $stdoutStreams = removeFieldScopeProtection(WorkerManager::class, 'stdoutStreams')
      ->getValue($workerManager);

    self::assertNotEmpty($stdoutStreams, 'Stdout streams must not be empty after we attached a worker.');

    foreach ($stdoutStreams as $stdoutStream)
    {
      self::assertEquals(
        TEST_STREAM_NON_BLOCKING_MODE,
        stream_get_meta_data($stdoutStream)['blocked'],
        'We must have a non blocking mode for the streams.'
      );
    }

    $stderrStreams = removeFieldScopeProtection(WorkerManager::class, self::OTRA_FIELD_STDERR_STREAMS)
      ->getValue($workerManager);
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
    $processes = removeFieldScopeProtection(WorkerManager::class, 'processes');
    self::assertNotEmpty(
      $processes,
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
    self::experimentDetach(self::COMMAND_2);
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
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager = new WorkerManager();
    $workerManager->attach($worker);
    // Launching
    $workerManager->listen();

    // Testing
    $this->expectOutputString(
      CLI_GREEN . "\e[15;2]" . self::SUCCESS_MESSAGE . END_COLOR . ' ' . $worker->command . PHP_EOL
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
    $workerManager  = new WorkerManager();
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager->attach($worker);
    $workerBis = new Worker(self::COMMAND_2, self::SUCCESS_MESSAGE_2, self::VERBOSE);
    $workerManager->attach($workerBis);

    // Launching
    while (0 < count($workerManager::$workers))
      $workerManager->listen();

    // Testing
    $messageStart = CLI_GREEN . "\e[15;2]";
    $firstMessageEnd = END_COLOR . ' ' . self::COMMAND . PHP_EOL;

    $this->expectOutputString(
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      WorkerManager::ERASE_TO_END_OF_LINE . PHP_EOL . "\033[1A" .
      $messageStart . self::SUCCESS_MESSAGE . $firstMessageEnd .
      $messageStart . self::SUCCESS_MESSAGE_2 . END_COLOR . ' ' . self::COMMAND_2 . PHP_EOL
    );

    // Cleaning
    foreach($workerManager::$workers as &$worker)
      $workerManager->detach($worker);

    unset($workerManager);
  }
}
