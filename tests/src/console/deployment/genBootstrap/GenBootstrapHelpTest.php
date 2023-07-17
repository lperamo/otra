<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class GenBootstrapHelpTest extends TestCase
{
  private const
    OTRA_TASK_GEN_BOOTSTRAP = 'genBootstrap',
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
      str_pad(self::OTRA_TASK_GEN_BOOTSTRAP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Launch the genClassMap command and generates a file that contains all the necessary php files.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('genclassmap', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to 0, it prevents the generation/override of the class mapping file.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to 1, we print all the main warnings when the task fails. Put 2 to get every warning.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('lint', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Checks for syntax errors. 0 disabled, 1 enabled (defaults to 0)' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('route', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The route for which you want to generate the micro bootstrap.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_BOOTSTRAP]
    );
  }
}
