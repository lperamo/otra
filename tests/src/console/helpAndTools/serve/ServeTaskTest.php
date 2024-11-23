<?php
declare(strict_types=1);

namespace src\console\helpAndTools\serve;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_WARNING, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class ServeTaskTest extends TestCase
{
  private const string TASK_SERVE = 'serve';

  /**
   * @author Lionel Péramo
   */
  public function testServe() : void
  {
    // testing
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage(
      'Problem when loading the command :' . PHP_EOL .
      CLI_WARNING . 'OTRA_LIVE_APP_ENV=dev OTRA_LIVE_HTTPS=false php -d variables_order=EGPCS -S localhost:-50 -t /media/data/web/perso/otra/web web/indexDev.php 2>&1' . END_COLOR . PHP_EOL .
      'Shell error code 1. Invalid address: localhost:-50'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::TASK_SERVE,
      ['otra.php', self::TASK_SERVE, '-50']
    );
  }
}
