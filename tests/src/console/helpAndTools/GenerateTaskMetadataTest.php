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
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_CLASS_MAP = BASE_PATH . 'cache/php/init/' . self::TASKS_CLASSMAP_FILENAME,
    TASKS_HELP_FILENAME = 'tasksHelp.php',
    SHELL_COMPLETIONS_FILENAME = 'shellCompletions.sh',
    TASK_GENERATE_TASK_METADATA = 'generateTaskMetadata',
    OTRA_TASK_HELP = 'help',
    METADATA_EXAMPLES_PATH = TEST_PATH . 'examples/generateTaskMetadata/';

  /**
   * @author Lionel Péramo
   */
  public function testGenerateTaskMetadata() : void
  {
    // launching
    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::TASK_GENERATE_TASK_METADATA,
      ['otra.php', self::TASK_GENERATE_TASK_METADATA]
    );

    // testing
    $expectedFile = self::METADATA_EXAMPLES_PATH . self::TASKS_HELP_FILENAME;
    $fileToTest = self::PHP_CACHE_PATH . 'init/' . self::TASKS_HELP_FILENAME;
    self::assertFileExists(self::PHP_CACHE_PATH . 'init/' . self::TASKS_HELP_FILENAME);
    self::assertFileEquals(
      $expectedFile,
      $fileToTest,
      'Checking tasks help. ' . $expectedFile . ' vs ' . $fileToTest
    );

    $expectedFile = self::METADATA_EXAMPLES_PATH . self::TASKS_CLASSMAP_FILENAME;
    $fileToTest = self::PHP_CACHE_PATH . 'init/' . self::TASKS_CLASSMAP_FILENAME;
    self::assertFileExists(self::PHP_CACHE_PATH . 'init/' . self::TASKS_CLASSMAP_FILENAME);
    self::assertFileEquals(
      $expectedFile,
      $fileToTest,
      'Checking task classmap. ' . $expectedFile . ' vs ' . $fileToTest
    );

    $expectedFile = self::METADATA_EXAMPLES_PATH . self::SHELL_COMPLETIONS_FILENAME;
    $fileToTest = CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME;
    self::assertFileExists(CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME);
    self::assertFileEquals(
      $expectedFile,
      $fileToTest,
      'Checking shell completions. ' . $expectedFile . ' vs '. $fileToTest
    );

    self::expectOutputString(
      CLI_GREEN . 'Generation of help and task class map done.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Generation of shell completions script done.' . END_COLOR . PHP_EOL
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
      require self::TASKS_CLASS_MAP,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_GENERATE_TASK_METADATA]
    );
  }
}
