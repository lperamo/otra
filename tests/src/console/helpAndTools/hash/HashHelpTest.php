<?php
declare(strict_types=1);

namespace src\console\helpAndTools\hash;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class HashHelpTest extends TestCase
{
  private const string
    OTRA_TASK_HASH = 'hash',
    OTRA_TASK_HELP = 'help';

  /**
   * @throws OtraException
   */
  public function testHashHelp(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_HASH, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Returns a random hash.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('rounds', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The numbers of round for the blowfish salt. Default: 7.' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_HASH]
    );
  }
}
