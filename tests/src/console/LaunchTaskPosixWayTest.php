<?php
declare(strict_types=1);

namespace src\console;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\{ CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\launchTaskPosixWay;
use const otra\cache\php\CONSOLE_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class LaunchTaskPosixWayTest extends TestCase
{
  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test_missingRequiredParameter() : void
  {
    // context
    require CONSOLE_PATH . 'launchTaskPosixWay.php';

    // testing
    self::expectException(OtraException::class);
    self::expectOutputString(
      CLI_ERROR . 'The parameter ' . CLI_INFO_HIGHLIGHT . 'filename' . CLI_ERROR . ' is required!' . END_COLOR . PHP_EOL
    );

    // launching
    // We try to launch the `genServerConfig` task to create two Nginx configuration files for the development
    // environment
    launchTaskPosixWay(
      require TASK_CLASS_MAP_PATH,
      [
        'bin/otra.php',
        '-t=genServerConfig',
        '--environment=dev',
        '--tech=nginx'
      ],
      'genServerConfig'
    );
  }
}
