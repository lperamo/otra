<?php
declare(strict_types=1);

namespace src\console\deployment\clearCache;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class ClearCacheHelpTest extends TestCase
{
  private const OTRA_TASK_CLEAR_CACHE = 'clearCache',
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
      str_pad(self::OTRA_TASK_CLEAR_CACHE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Clears whatever cache you want to clear.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '  1 => PHP OTRA internal cache' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '  2 => PHP bootstraps' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '  4 => CSS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '  8 => JS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . ' 16 => Templates' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . ' 32 => Route management' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . ' 64 => Class mapping (development & production)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '128 => Console tasks metadata' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '256 => Security files' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '511 => All files from the cache (default)' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('route name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If you want to clear cache for only one route. (useful only for bits 2, 4, 8 of the ' .
      CLI_INFO_HIGHLIGHT . 'mask' . CLI_INFO . ' parameter)' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CLEAR_CACHE]
    );
  }
}
