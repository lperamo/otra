<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CheckConfigurationTest extends TestCase
{
  private const OTRA_TASK_CHECK_CONFIGURATION = 'checkConfiguration',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCheckConfiguration() : void
  {
    // testing
    $this->expectOutputString('Checking routes configuration...' . PHP_EOL);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      ['otra.php', self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  public function testCheckConfiguration_NoBundles() : void
  {
    // testing
    $this->expectOutputString(CLI_ERROR . 'There are no bundles to use!' . END_COLOR . PHP_EOL);
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('');

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      ['otra.php', self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testCheckConfigurationHelp() : void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CHECK_CONFIGURATION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO . 'Checks route configuration files structure.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }
}
