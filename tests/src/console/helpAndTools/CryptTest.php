<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CryptTest extends TestCase
{
  private const OTRA_TASK_CRYPT = 'crypt',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   */
  public function testCrypt() : void
  {
    // testing
    $this->expectOutputRegex(
      '@' . preg_quote(CLI_LIGHT_CYAN) . 'salt\s\(hexadecimal\sversion\)\s:\s' .
      preg_quote(END_COLOR) . '[a-f0-9]{32}\s' . preg_quote(CLI_LIGHT_CYAN) . 'password\s{19}:\s' .
      preg_quote(END_COLOR) . '[a-f0-9]{20}\s@'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CRYPT,
      ['otra.php', self::OTRA_TASK_CRYPT, 'password', 20000]
    );
  }

  public function testCryptHelp() : void
  {
    // testing
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_CRYPT, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN . 'Crypts a password and shows it.' . PHP_EOL .
      CLI_LIGHT_CYAN .
      '   + ' . str_pad('password', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_CYAN . 'The password to crypt.' . PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('iterations', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_CYAN . 'The number of internal iterations to perform for the derivation.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CRYPT]
    );
  }
}
