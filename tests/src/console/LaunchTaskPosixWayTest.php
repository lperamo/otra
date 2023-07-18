<?php
declare(strict_types=1);

namespace src\console;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{CONSOLE_PATH, DEV};
use const otra\console\{ CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\launchTaskPosixWay;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class LaunchTaskPosixWayTest extends TestCase
{
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
        '--environment=' . DEV,
        '--tech=nginx'
      ],
      'genServerConfig'
    );
  }
}
