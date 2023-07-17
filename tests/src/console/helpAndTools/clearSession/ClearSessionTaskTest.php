<?php
declare(strict_types=1);

namespace src\console\helpAndTools\clearSession;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\CACHE_PATH;
use const otra\console\
{CLI_BASE, CLI_SUCCESS, CLI_WARNING, END_COLOR, ERASE_SEQUENCE, SUCCESS};

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
      CLI_BASE . 'Clearing the session data...' . PHP_EOL . ERASE_SEQUENCE . 'Session data cleared' . CLI_SUCCESS .
      ' ✔'. CLI_WARNING . PHP_EOL .
      '/!\ Do not forget to clear session cookies on client side via JavaScript or the browser development tools.' .
      END_COLOR . PHP_EOL
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
