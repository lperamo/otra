<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\serve;

use otra\OtraException;
use function otra\tools\cliCommand;
use const otra\cache\php\{BASE_PATH,CORE_PATH,DEV};
use const otra\console\{CLI_INFO, CLI_WARNING, END_COLOR};

const
  PARAMETER_PORT = 2,
  PARAMETER_ENV = 3,
  OTRA_LIVE_HTTPS = 'false';

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException
 * @return void
 */
function serve(array $argumentsVector): void
{
  require CORE_PATH . 'tools/cli.php';
  define(__NAMESPACE__ . '\\OTRA_APP_PORT', $argumentsVector[PARAMETER_PORT] ?? 8000);
  define(__NAMESPACE__ . '\\OTRA_LIVE_APP_ENV', $argumentsVector[PARAMETER_ENV] ?? DEV);

  $xdebugLoaded = extension_loaded('xdebug');

  echo CLI_INFO, 'Launching a PHP web internal server on localhost:', OTRA_APP_PORT, ' in ',
    OTRA_LIVE_APP_ENV === DEV
      ? 'development'
      : 'production',
    ' mode.', END_COLOR, PHP_EOL;

  // Explicit notification if JIT is disabled
  if ($xdebugLoaded)
  {
    echo CLI_WARNING,
      'JIT compiler has been automatically disabled because Xdebug extension is loaded.', PHP_EOL,
      'Remove Xdebug to benefit from JIT performance improvements!',
      END_COLOR,
      PHP_EOL;
  }

  // Dynamic construction of the command
  $command = 'OTRA_LIVE_APP_ENV=' . OTRA_LIVE_APP_ENV .
    ' OTRA_LIVE_HTTPS=' . OTRA_LIVE_HTTPS .
    ' php' . ($xdebugLoaded ? ' -d opcache.jit=disable' : '') . // Conditional JIT parameter injection
    ' -d variables_order=EGPCS -S localhost:' . OTRA_APP_PORT .
    ' -t ' . BASE_PATH . 'web web/index';

  if (OTRA_LIVE_APP_ENV === DEV)
    $command .= 'Dev';

  cliCommand($command . '.php 2>&1');
}
