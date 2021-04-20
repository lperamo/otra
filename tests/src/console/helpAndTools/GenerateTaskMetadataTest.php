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
      require TASK_CLASS_MAP_PATH,
      self::TASK_GENERATE_TASK_METADATA,
      ['otra.php', self::TASK_GENERATE_TASK_METADATA]
    );

    // testing
    $expectedFile = self::METADATA_EXAMPLES_PATH . self::TASKS_HELP_FILENAME;
    $fileToTest = CACHE_PHP_INIT_PATH . self::TASKS_HELP_FILENAME;
    self::assertFileExists(CACHE_PHP_INIT_PATH . self::TASKS_HELP_FILENAME);
    self::assertFileEquals(
      $expectedFile,
      $fileToTest,
      'Checking tasks help. ' . $expectedFile . ' vs ' . $fileToTest
    );

    $expectedFile = self::METADATA_EXAMPLES_PATH . self::TASKS_CLASSMAP_FILENAME;
    self::assertFileExists(TASK_CLASS_MAP_PATH);
    self::assertFileEquals(
      $expectedFile,
      TASK_CLASS_MAP_PATH,
      'Checking task classmap. ' . $expectedFile . ' vs ' . TASK_CLASS_MAP_PATH
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
      CLI_BASE . 'Generation of help and task class map done' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_BASE . 'Generation of shell completions script done' . CLI_SUCCESS .' ✔' . END_COLOR . PHP_EOL
    );
  }

  public function testGenerateTaskMetadataHelp()
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
