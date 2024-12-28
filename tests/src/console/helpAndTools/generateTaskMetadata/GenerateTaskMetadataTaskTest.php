<?php
declare(strict_types=1);

namespace src\console\helpAndTools\generateTaskMetadata;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH,TEST_PATH};
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};
use const otra\bin\{CACHE_PHP_INIT_PATH,TASK_CLASS_MAP_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class GenerateTaskMetadataTaskTest extends TestCase
{
  private const string
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
      'Checking tasks help. ' . CLI_INFO_HIGHLIGHT . $expectedFile . CLI_ERROR . ' vs ' . CLI_INFO_HIGHLIGHT .
      $fileToTest . CLI_ERROR
    );

    $expectedFile = self::METADATA_EXAMPLES_PATH . self::TASKS_CLASSMAP_FILENAME;
    self::assertFileExists(TASK_CLASS_MAP_PATH);

    $expectedClassMap = require $expectedFile;
    $actualClassMap = require TASK_CLASS_MAP_PATH;

    // We sort the arrays by key to prevent give false negative because of the order
    ksort($expectedClassMap);
    ksort($actualClassMap);

    self::assertSame(
      $expectedClassMap,
      $actualClassMap,
      'Checking task classmap. ' . CLI_INFO_HIGHLIGHT . $expectedFile . CLI_ERROR . ' vs ' .
      CLI_INFO_HIGHLIGHT . TASK_CLASS_MAP_PATH . CLI_ERROR
    );

    $expectedFile = self::METADATA_EXAMPLES_PATH . self::SHELL_COMPLETIONS_FILENAME;
    $fileToTest = CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME;
    self::assertFileExists(CONSOLE_PATH . 'shellCompletions/' . self::SHELL_COMPLETIONS_FILENAME);
    self::assertFileEquals(
      $expectedFile,
      $fileToTest,
      'Checking shell completions. ' . CLI_INFO_HIGHLIGHT . $expectedFile . CLI_ERROR . ' vs '.
      CLI_INFO_HIGHLIGHT . $fileToTest . CLI_ERROR
    );

    self::expectOutputString(
      CLI_BASE . 'Generation of help and task class map done' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_BASE . 'Generation of shell completions script done' . CLI_SUCCESS .' ✔' . END_COLOR . PHP_EOL
    );
  }
}
