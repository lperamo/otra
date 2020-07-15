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
    COMMAND = 'sleep',
    SUCCESS_MESSAGE = 'hello',
    VERBOSE = 0;

  /**
   * @author Lionel Péramo
   */
  public function testWorkerManagerConstruct() : void
  {
    // launching
    $manager = new WorkerManager();

    // testing
    self::assertInstanceOf(WorkerManager::class, $manager);
  }

  /**
   * @depends testWorkerManagerConstruct
   *
   * @throws ReflectionException
   * @author Lionel Péramo
   */
  public function testWorkerManagerDestruct() : void
  {
    // launching
    $workerManager = new WorkerManager();
    $workerManager->__destruct();

    // testing streams
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, 'stdinStreams')
      ->getValue($workerManager)
    );
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, 'stdoutStreams')
        ->getValue($workerManager)
    );
    self::assertEmpty(
      removeFieldScopeProtection(WorkerManager::class, 'stderrStreams')
        ->getValue($workerManager)
    );
  }

  /**
   * @depends testWorkerManagerConstruct
   * @depends testWorkerManagerDestruct
   * @throws ReflectionException
   *
   * @author Lionel Péramo
   */
  public function testAttachAndDetach() : void
  {
    // context
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager  = new WorkerManager();
    define('TEST_STREAM_NON_BLOCKING_MODE', false);

    // launching
    $workerManager->attach($worker);

    // 1. testing streams
    $stdinStreams = removeFieldScopeProtection(WorkerManager::class, 'stdinStreams')
      ->getValue($workerManager);
    self::assertNotEmpty($stdinStreams);

    $stdoutStreams = removeFieldScopeProtection(WorkerManager::class, 'stdoutStreams')
      ->getValue($workerManager);

    self::assertNotEmpty($stdoutStreams);

    foreach ($stdoutStreams as $stdoutStream)
    {
      self::assertEquals(TEST_STREAM_NON_BLOCKING_MODE, stream_get_meta_data($stdoutStream)['blocked']);
    }

    $stderrStreams = removeFieldScopeProtection(WorkerManager::class, 'stderrStreams')
      ->getValue($workerManager);
    self::assertNotEmpty($stderrStreams);

    // 2. testing workers
    self::assertCount(1, $workerManager::$workers);
    self::assertContainsOnly(Worker::class, $workerManager::$workers);

    // 3. testing processes
    $processes = removeFieldScopeProtection(WorkerManager::class, 'processes');
    self::assertNotEmpty($processes);

    // cleaning
    $workerManager->__destruct();
  }
}
