<?php
declare(strict_types=1);

namespace src\console\helpAndTools\crypt;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CryptTaskTest extends TestCase
{
  private const string OTRA_TASK_CRYPT = 'crypt';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCrypt() : void
  {
    // testing
    $this->expectOutputRegex(
      '@' . preg_quote(CLI_INFO_HIGHLIGHT) . 'salt\s\(hexadecimal\sversion\)\s:\s' .
      preg_quote(END_COLOR) . '[a-f0-9]{32}\s' . preg_quote(CLI_INFO_HIGHLIGHT) . 'password\s{19}:\s' .
      preg_quote(END_COLOR) . '[a-f0-9]{20}\s@'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CRYPT,
      ['otra.php', self::OTRA_TASK_CRYPT, 'password', 20000]
    );
  }
}
