<?php
declare(strict_types=1);

namespace src\console\deployment\deploy;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\
{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;


/**
 * @runTestsInSeparateProcesses
 */
class DeployHelpTest extends TestCase
{
  private const
    OTRA_TASK_DEPLOY = 'deploy',
    OTRA_TASK_HELP = 'help';
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
      str_pad(self::OTRA_TASK_DEPLOY, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Deploy the site. ' . CLI_WARNING . '[Currently only works for unix systems !]' . END_COLOR .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '0 => Nothing to do (default)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => Generates PHP production files.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => JS production files.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => CSS production files' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => Templates, JSON manifest and SVGs' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '15 => all production files' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to 1 => we print all the warnings during the production php files generation' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_DEPLOY]
    );
  }
}
