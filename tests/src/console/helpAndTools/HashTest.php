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
class HashTest extends TestCase
{
  private const
    OTRA_TASK_HASH = 'hash',
    OTRA_TASK_HELP = 'help',
    BLOWFISH_SALT_LENGTH = 22;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testHash() : void
  {
    // testing
    $this->expectOutputRegex('@\$2y\$03\$[a-zA-Z0-9]{' . self::BLOWFISH_SALT_LENGTH . '}@');

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HASH,
      ['otra.php', self::OTRA_TASK_HASH, 3]
    );
  }

  /**
   * @throws OtraException
   */
  public function testHashHelp()
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
