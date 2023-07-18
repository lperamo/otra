<?php
declare(strict_types=1);

namespace src\console\deployment\genClassMap;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenClassMapHelpTest extends TestCase
{
  private const
    OTRA_TASK_GEN_CLASS_MAP = 'genClassMap',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testGenClassMapHelp(): void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_GEN_CLASS_MAP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates a class mapping file that will be used to replace the autoloading method.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to 1 => Show all the classes that will be used. Default to 0.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_CLASS_MAP]
    );
  }
}
