<?php
declare(strict_types=1);

namespace src\console;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, CONSOLE_PATH, DEV, PROD};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\launchTask;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class LaunchTaskTest extends TestCase
{
  private const string
    CONF_FILE_NAME = 'test.conf',
    CACHE_CONF_FILE_NAME = 'test_cache.conf',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld';

  protected function tearDown(): void
  {
    parent::tearDown();

    if (file_exists(self::CONF_FILE_NAME))
      unlink(self::CONF_FILE_NAME);

    if (file_exists(self::CACHE_CONF_FILE_NAME))
      unlink(self::CACHE_CONF_FILE_NAME);
  }

  /**
   * @medium
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
    require CONSOLE_PATH . 'launchTask.php';

    // launching
    // We try to launch the `genServerConfig` task to create two Nginx configuration files for the development
    // environment
    ob_start();
    launchTask(
      require TASK_CLASS_MAP_PATH,
      [
        'bin/otra.php',
        'genServerConfig',
        self::CONF_FILE_NAME,
        DEV,
        'nginx'
      ],
      5,
      'genServerConfig'
    );

    self::assertSame(
      CLI_BASE . 'Nginx development server configuration generated in ' . CLI_INFO_HIGHLIGHT .
      self::CONF_FILE_NAME . CLI_BASE . ' and the cache configuration in ' . CLI_INFO_HIGHLIGHT . 'test_cache.conf' .
      CLI_BASE . '.' . PHP_EOL . 'Do not forget to include the cache file in your main server configuration file!' .
      END_COLOR . PHP_EOL,
      ob_get_clean()
    );
  }
}
