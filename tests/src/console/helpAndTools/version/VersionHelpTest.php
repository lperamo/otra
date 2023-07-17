<?php
declare(strict_types=1);

namespace src\console\helpAndTools\version;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class VersionHelpTest extends TestCase
{
  private const
    TASK_VERSION = 'version',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVersionHelp(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_VERSION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Shows the framework version.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_VERSION]
    );
  }
}
