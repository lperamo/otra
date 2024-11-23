<?php
declare(strict_types=1);

namespace src\console\helpAndTools\generateTaskMetadata;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class GenerateTaskMetadataHelpTest extends TestCase
{
  private const string
    TASK_GENERATE_TASK_METADATA = 'generateTaskMetadata',
    OTRA_TASK_HELP = 'help';

  /**
   * @throws OtraException
   */
  public function testGenerateTaskMetadataHelp(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_GENERATE_TASK_METADATA, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates files that are used to show the help, finds quickly all the tasks and gives shell completions.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_GENERATE_TASK_METADATA]
    );
  }
}
