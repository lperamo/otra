<?php
declare(strict_types=1);

namespace src\console\helpAndTools\serve;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\TEST_PATH;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tests\tools\taskParameter;

/**
 * @runTestsInSeparateProcesses
 */
class ServeHelpTest extends TestCase
{
  private const
    TASK_SERVE = 'serve',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testServeHelp()
  {
    // context
    require TEST_PATH . 'tools.php';

    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_SERVE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates a PHP web internal server.' .
      PHP_EOL .
      taskParameter(
        'port',
        'The port used by the server ... Defaults to 8000',
        TasksManager::OPTIONAL_PARAMETER
      ) .
      taskParameter(
        'env',
        'Environment mode [dev,prod]. Defaults to \'dev\'.',
        TasksManager::OPTIONAL_PARAMETER
      ) . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_SERVE]
    );
  }
}
