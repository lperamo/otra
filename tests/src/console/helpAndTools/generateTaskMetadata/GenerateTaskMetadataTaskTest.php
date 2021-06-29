<?php
declare(strict_types=1);

namespace src\console\helpAndTools\generateTaskMetadata;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH,TEST_PATH};
use const otra\console\{CLI_BASE, CLI_SUCCESS, END_COLOR};
use const otra\bin\{CACHE_PHP_INIT_PATH,TASK_CLASS_MAP_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class GenerateTaskMetadataTaskTest extends TestCase
{
  private const
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_HELP_FILENAME = 'tasksHelp.php',
    SHELL_COMPLETIONS_FILENAME = 'shellCompletions.sh',
    TASK_GENERATE_TASK_METADATA = 'generateTaskMetadata',
    METADATA_EXAMPLES_PATH = TEST_PATH . 'examples/generateTaskMetadata/';

  /**
   * @author Lionel Péramo
   * @throws OtraException
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
}