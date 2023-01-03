<?php
declare(strict_types=1);

namespace src\console\helpAndTools\requirements;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class RequirementsHelpTest extends TestCase
{
  private const
    TASK_REQUIREMENTS = 'requirements',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequirementsHelp(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_REQUIREMENTS, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Shows the requirements to use OTRA at its maximum capabilities.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_REQUIREMENTS]
    );
  }
}
