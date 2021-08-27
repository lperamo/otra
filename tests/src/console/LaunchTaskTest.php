<?php
declare(strict_types=1);

namespace src\console;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, CONSOLE_PATH, DEV, PROD};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\launchTask;

/**
 * @runTestsInSeparateProcesses
 */
class LaunchTaskTest extends TestCase
{
  private const CONF_FILE_NAME = 'test.conf';

  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
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

    self::assertEquals(
      CLI_BASE . 'Nginx development server configuration generated in ' . CLI_INFO_HIGHLIGHT .
      self::CONF_FILE_NAME . CLI_BASE . ' and the cache configuration in ' . CLI_INFO_HIGHLIGHT . 'test_cache.conf' .
      CLI_BASE . '.' . PHP_EOL . 'Do not forget to include the cache file in your main server configuration file!' .
      END_COLOR . PHP_EOL,
      ob_get_clean()
    );
  }
}