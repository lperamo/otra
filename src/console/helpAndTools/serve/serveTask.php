<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

require CORE_PATH . 'tools/cli.php';

const PARAMETER_PORT = 2;
const PARAMETER_ENV = 3;
define('OTRA_APP_PORT', $argv[PARAMETER_PORT] ?? 8000);
define('OTRA_LIVE_APP_ENV', $argv[PARAMETER_ENV] ?? DEV);
const OTRA_LIVE_HTTPS = 'false';

echo CLI_INFO, 'Launching a PHP web internal server on localhost:', OTRA_APP_PORT, ' in ',
  OTRA_LIVE_APP_ENV === DEV
    ? 'development'
    : 'production',
  ' mode.', END_COLOR, PHP_EOL;

$command = 'OTRA_LIVE_APP_ENV=' . OTRA_LIVE_APP_ENV . ' OTRA_LIVE_HTTPS=' . OTRA_LIVE_HTTPS .
  ' php -d variables_order=EGPCS -S localhost:' . OTRA_APP_PORT . ' -t ' . BASE_PATH . 'web web/index';

if (OTRA_LIVE_APP_ENV === DEV)
  $command .= 'Dev';

// We use redirections to be able to use unit tests (stderr blocks PHPUnit execution)
cliCommand($command . '.php 2>&1');
