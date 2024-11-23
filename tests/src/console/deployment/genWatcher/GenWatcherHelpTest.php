<?php
declare(strict_types=1);

namespace src\console\deployment\genWatcher;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\
{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenWatcherHelpTest extends TestCase
{
  private const string
    OTRA_TASK_GEN_WATCHER = 'genWatcher',
    OTRA_TASK_HELP = 'help',
    LABEL_PLUS = '   + ';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_GEN_WATCHER, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Launches a watcher that will update the PHP class mapping, the ts files and the scss files.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '0 => Only tells that the watcher is started.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => Tells which file has been updated (default).' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING .
      '2 => Tells which file has been updated and the most important events that have been triggered.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Defaults to 1.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => SCSS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => TS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => routes' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => PHP (routes, configuration and class mapping)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '15 => ALL. Defaults to 15.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('gcc', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Should we use Google Closure Compiler for javascript/typescript files?' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('no SASS cache', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Do we have to clean the SASS/SCSS cache first? (Defaults to 0)' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_WATCHER]
    );
  }
}
