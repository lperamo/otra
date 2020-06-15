<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class GenerateTaskMetadataTest extends TestCase
{
  private const PHP_CACHE_PATH = CACHE_PATH . 'php/',
    TASKS_HELP_FILENAME = 'tasksHelp.php',
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    SHELL_COMPLETIONS_FILENAME = 'shellCompletions.sh',
    TASK_GENERATE_TASK_METADATA = 'generateTaskMetadata',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   */
  public function testGenerateTaskMetadata() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

    // testing
    self::assertFileExists(self::PHP_CACHE_PATH  . self::TASKS_HELP_FILENAME);
    self::assertFileEquals(
      TEST_PATH . 'examples/generateTaskMetadata/' . self::TASKS_HELP_FILENAME,
      self::PHP_CACHE_PATH . self::TASKS_HELP_FILENAME
    );

    self::assertFileExists(self::PHP_CACHE_PATH  . self::TASKS_CLASSMAP_FILENAME);
    self::assertFileEquals(
      TEST_PATH . 'examples/generateTaskMetadata/' . self::TASKS_CLASSMAP_FILENAME,
      self::PHP_CACHE_PATH . self::TASKS_CLASSMAP_FILENAME
    );

    self::assertFileExists(CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME);
    self::assertFileEquals(
      TEST_PATH . 'examples/generateTaskMetadata/' . self::SHELL_COMPLETIONS_FILENAME,
      CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME
    );

    self::expectOutputString(
      CLI_GREEN . 'Generation of help and task class map done.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Generation of shell completions script done.' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_GENERATE_TASK_METADATA,
      ['otra.php', self::TASK_GENERATE_TASK_METADATA]
    );
  }

  public function testGenerateTaskMetadataHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::TASK_GENERATE_TASK_METADATA, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Generates files that are used to show the help, finds quickly all the tasks and gives shell completions.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require BASE_PATH . 'cache/php/tasksClassMap.php',
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_GENERATE_TASK_METADATA]
    );
  }
}
