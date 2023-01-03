<?php
declare(strict_types=1);

namespace src\console\deployment\genJsRouting;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class GenJsRoutingHelpTest extends TestCase
{
  private const OTRA_TASK_GEN_JS_ROUTING = 'genJsRouting',
    OTRA_TASK_HELP = 'help';
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  /**
   * @throws OtraException
   */
  public function test(): void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_GEN_JS_ROUTING, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates a route mapping that can be used by JavaScript files.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_JS_ROUTING]
    );
  }
}
