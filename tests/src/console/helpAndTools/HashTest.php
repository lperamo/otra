<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

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

  public function testHashHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_HASH, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Returns a random hash.' .
      PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('rounds', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_CYAN . 'The numbers of round for the blowfish salt. Default: 7.' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_HASH]
    );
  }
}
