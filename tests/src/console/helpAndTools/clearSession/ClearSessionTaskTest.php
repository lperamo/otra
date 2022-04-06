<?php
declare(strict_types=1);

namespace src\console\helpAndTools\clearSession;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\CACHE_PATH;
use const otra\console\{CLI_BASE, ERASE_SEQUENCE, SUCCESS};

/**
 * @runTestsInSeparateProcesses
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 */
class ClearSessionTaskTest extends TestCase
{
  private const
    OTRA_TASK_CLEAR_SESSION = 'clearSession',
    OTRA_PHP_BINARY = 'otra.php',
    TEST_FILE = CACHE_PATH . 'php/sessions/test.php';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    touch(self::TEST_FILE);

    // testing
    $this->expectOutputString(
      CLI_BASE . 'Clearing the session data...' . PHP_EOL . ERASE_SEQUENCE . 'Session data cleared' . SUCCESS
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_SESSION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CLEAR_SESSION]
    );

    // testing
    self::assertFalse(file_exists(self::TEST_FILE));
  }
}
