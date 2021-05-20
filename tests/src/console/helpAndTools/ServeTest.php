<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\TEST_PATH;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tests\tools\taskParameter;

/**
 * @runTestsInSeparateProcesses
 */
class ServeTest extends TestCase
{
  private const
    TASK_SERVE = 'serve',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   */
  public function testServe() : void
  {
    // testing
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage(
      'Problem when loading the command :' . PHP_EOL .
      CLI_WARNING . 'OTRA_LIVE_APP_ENV=dev OTRA_LIVE_HTTPS=false php -d variables_order=EGPCS -S localhost:-50 -t /var/www/html/perso/otra/web web/indexDev.php 2>&1' . END_COLOR . PHP_EOL .
      'Shell error code 1. Invalid address: localhost:-50'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::TASK_SERVE,
      ['otra.php', self::TASK_SERVE, '-50']
    );
  }

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
