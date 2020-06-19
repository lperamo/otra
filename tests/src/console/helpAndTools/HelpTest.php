<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HelpTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
  }

  protected function tearDown(): void
  {
    parent::tearDown();
  }

  /**
   * @author Lionel Péramo
   */
  public function testHelp() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

    // testing


    // launching
    TasksManager::execute(
      $tasksClassMap,
      OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', OTRA_TASK_CREATE_HELLO_WORLD]
    );

    // cleaning

  }
}
