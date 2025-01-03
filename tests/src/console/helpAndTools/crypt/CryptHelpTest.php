<?php
declare(strict_types=1);

namespace src\console\helpAndTools\crypt;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CryptHelpTest extends TestCase
{
  private const string
    OTRA_TASK_CRYPT = 'crypt',
    OTRA_TASK_HELP = 'help';

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
