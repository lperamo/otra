<?php
declare(strict_types=1);

namespace src\console\helpAndTools\clearSession;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\
{
  CLI_BASE,
  CLI_GRAY,
  CLI_INFO,
  END_COLOR
};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ClearSessionHelpTest extends TestCase
{
  private const string
    OTRA_TASK_CLEAR_SESSION = 'clearSession',
    OTRA_TASK_HELP = 'help',
    OTRA_PHP_BINARY = 'otra.php';

  /**
   * @throws OtraException
   */
  public function test() : void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CLEAR_SESSION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO . 'Clears all things related to the session : PHP session and OTRA session.' .
      PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_HELP, self::OTRA_TASK_CLEAR_SESSION]
    );
  }
}
