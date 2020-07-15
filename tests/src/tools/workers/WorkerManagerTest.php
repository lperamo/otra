<?php
declare(strict_types=1);

namespace src\tools\workers;

use ReflectionException;
use otra\tools\workers\WorkerManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WorkerManagerTest extends TestCase
{
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
}
