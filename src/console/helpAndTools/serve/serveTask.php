<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

require CORE_PATH . 'tools/cli.php';

define('PARAMETER_PORT', 2);
define('PARAMETER_ENV', 3);
define('OTRA_APP_PORT', $argv[PARAMETER_PORT] ?? 8000);
define('OTRA_LIVE_APP_ENV', $argv[PARAMETER_ENV] ?? 'dev');
define('OTRA_LIVE_HTTPS', 'false');

echo CLI_INFO, 'Launching a PHP web internal server on localhost:', OTRA_APP_PORT, ' in ',
  OTRA_LIVE_APP_ENV === 'dev'
    ? 'development'
    : 'production',
  ' mode.', END_COLOR, PHP_EOL;

$command = 'OTRA_LIVE_APP_ENV=' . OTRA_LIVE_APP_ENV . ' OTRA_LIVE_HTTPS=' . OTRA_LIVE_HTTPS .
  ' php -d variables_order=EGPCS -S localhost:' . (string)OTRA_APP_PORT . ' -t ' . BASE_PATH . 'web web/index';

if (OTRA_LIVE_APP_ENV === 'dev')
  $command .= 'Dev';

// We use redirections to be able to use unit tests (stderr blocks PHPUnit execution)
cliCommand($command . '.php 2>&1');
