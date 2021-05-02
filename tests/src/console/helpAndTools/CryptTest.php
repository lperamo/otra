<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CryptTest extends TestCase
{
  private const OTRA_TASK_CRYPT = 'crypt',
    OTRA_TASK_HELP = 'help';

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

  /**
   * @throws OtraException
   */
  public function testCryptHelp() : void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CRYPT, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO . 'Crypts a password and shows it.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('password', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The password to crypt.' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('iterations', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The number of internal iterations to perform for the derivation.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CRYPT]
    );
  }
}
