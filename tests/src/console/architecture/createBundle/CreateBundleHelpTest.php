<?php
declare(strict_types=1);

namespace src\console\architecture\createBundle;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CreateBundleHelpTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_BUNDLE = 'createBundle',
    OTRA_TASK_HELP = 'help',
    LABEL_PLUS = '   + ';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CREATE_BUNDLE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates a bundle.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('bundle-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The name of the bundle!' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'In addition to the module, it will create a folder for :' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 => nothing (default)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => config' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => models' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => resources' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => views' . PHP_EOL .
      CLI_INFO_HIGHLIGHT . self::LABEL_PLUS .
      str_pad('interactive', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'If set to false, no question will be asked but the status messages are shown. Defaults to true.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('force', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'If set to true, create intermediary steps (like folders) if they are missing. Defaults to false.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CREATE_BUNDLE]
    );
  }
}
