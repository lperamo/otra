<?php
declare(strict_types=1);

namespace src\console\architecture\createGlobalConstants;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\
{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_WARNING, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;


/**
 * @runTestsInSeparateProcesses
 */
class CreateGlobalConstantsHelpTest extends TestCase
{

  private const OTRA_TASK_CREATE_GLOBAL_CONSTANTS = 'createGlobalConstants',
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
      str_pad(self::OTRA_TASK_CREATE_GLOBAL_CONSTANTS, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates OTRA global constants. ' . CLI_WARNING .
      'Only use it if you have changed the project folder or OTRA vendor folder location.' . CLI_INFO .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CREATE_GLOBAL_CONSTANTS]
    );
  }
}
