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
    SUCCESS_MESSAGE = 'hello',
    VERBOSE = 0;

  /**
   * @author Lionel Péramo
   */
  public function testConstruct() : void
  {
    // launching
    $manager = new WorkerManager();

    // testing
    self::assertInstanceOf(WorkerManager::class, $manager);
  }

  /**
   * @depends testConstruct
   *
   * @throws ReflectionException
   * @author Lionel Péramo
   */
  public function testDestruct() : void
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
   * @depends testConstruct
   * @depends testDestruct
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

  /**
   * @depends testConstruct
   * @depends testDestruct
   * @depends testAttach
   * @throws ReflectionException
   */
  public function testDetach() : void
  {
    // context
    $worker = new Worker(self::COMMAND, self::SUCCESS_MESSAGE, self::VERBOSE);
    $workerManager  = new WorkerManager();
    $workerManager->attach($worker);
    define('TEST_DETACH_STATUS_SUCCESS', 0);

    // launching
    $foundKey = array_search($worker, $workerManager::$workers, true);
    $status = $workerManager->detach($worker);

    // testing workers
    self::assertArrayNotHasKey($foundKey, $workerManager::$workers);

    // testing processes
    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, 'processes')->getValue($workerManager)
    );

    // testing streams
    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, 'stdinStreams')->getValue($workerManager)
    );

    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, 'stdoutStreams')->getValue($workerManager)
    );

    self::assertArrayNotHasKey(
      $foundKey,
      removeFieldScopeProtection(WorkerManager::class, 'stderrStreams')->getValue($workerManager)
    );

    // detachment successful
    self::assertIsInt($status);
    self::assertEquals(TEST_DETACH_STATUS_SUCCESS, $status);
  }
}
