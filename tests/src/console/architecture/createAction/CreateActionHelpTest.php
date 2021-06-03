<?php
declare(strict_types=1);

namespace src\console\architecture\createAction;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;


/**
 * @runTestsInSeparateProcesses
 */
class CreateActionHelpTest extends TestCase
{
  private const OTRA_TASK_CREATE_ACTION = 'createAction',
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
      str_pad(self::OTRA_TASK_CREATE_ACTION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates actions.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('bundle', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The bundle where you want to put actions' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('module', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The module where you want to put actions' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('controller', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The controller where you want to put actions' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('action', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The name of the action!' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('interactive', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'If set to false, no question will be asked but the status messages are shown. Defaults to true.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('force', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'If set to true, create intermediary steps (like folders) if they are missing. Defaults to false.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CREATE_ACTION]
    );
  }
}
