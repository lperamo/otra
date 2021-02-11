<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ServeTest extends TestCase
{
  private const
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_CLASS_MAP = BASE_PATH . 'cache/php/' . self::TASKS_CLASSMAP_FILENAME,
    TASK_SERVE = 'serve',
    OTRA_TASK_HELP = 'help',
    DISABLE_FUNCTIONS = 'disable_functions';

  protected function setUp(): void
  {
    parent::setUp();
  }

  protected function tearDown(): void
  {
    parent::tearDown();
  }

   /**
    * @param string $parameter
    * @param string $description
    * @param string $requiredOrOptional 'required' or 'optional'
    *
    * @return string
    */
  private static function taskParameter(string $parameter, string $description, string $requiredOrOptional) : string
  {
    return CLI_LIGHT_CYAN . '   + ' .
      str_pad($parameter, TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) . CLI_LIGHT_GRAY . ': ' .
      CLI_LIGHT_CYAN . '(' . $requiredOrOptional . ') ' . CLI_CYAN . $description . PHP_EOL;
  }

  /**
   * @author Lionel Péramo
   */
  public function testServe() : void
  {
    // context

    // testing
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage(
      'Problem when loading the command :' . PHP_EOL .
      CLI_LIGHT_YELLOW . 'OTRA_LIVE_APP_ENV=dev OTRA_LIVE_HTTPS=false php -d variables_order=EGPCS -S localhost:-50 -t /var/www/html/perso/otra/web web/indexDev.php 2>&1' . END_COLOR . PHP_EOL .
      'Shell error code 1. Invalid address: localhost:-50'
    );

    // launching
    TasksManager::execute(
      require BASE_PATH . 'cache/php/tasksClassMap.php',
      self::TASK_SERVE,
      ['otra.php', self::TASK_SERVE, '-50']
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testServeHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::TASK_SERVE, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Creates a PHP web internal server.' .
      PHP_EOL .
      self::taskParameter(
        'port',
        'The port used by the server ... Defaults to 8000',
        TasksManager::OPTIONAL_PARAMETER
      ) .
      self::taskParameter(
        'env',
        'Environment mode [dev,prod]. Defaults to \'dev\'.',
        TasksManager::OPTIONAL_PARAMETER
      ) . END_COLOR
    );

    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_SERVE]
    );
  }
}
