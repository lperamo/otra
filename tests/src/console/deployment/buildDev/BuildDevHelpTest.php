<?php
declare(strict_types=1);

namespace src\console\deployment\buildDev;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class BuildDevHelpTest extends TestCase
{
  private const OTRA_TASK_BUILD_DEV = 'buildDev',
    OTRA_TASK_HELP = 'help';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_BUILD_DEV, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Compiles the typescripts, sass and php configuration files (modulo the binary mask).' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '0 => Quite silent, 1 => Tells which file has been updated.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => SCSS, 2 => TS, ..., 4 => routes, ..., 8 => PHP, 15 => ALL. Default to 15.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('gcc', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Should we use Google Closure Compiler for javascript/typescript files ? Defaults to false.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('scope', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '0 => project files (default), 1 => OTRA files, 2 => All the files' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_BUILD_DEV]
    );
  }
}
