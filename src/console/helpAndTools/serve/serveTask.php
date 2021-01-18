<?php
declare(strict_types=1);
require CORE_PATH . 'tools/cli.php';

define('PARAMETER_PORT', 2);
define('PARAMETER_ENV', 3);
define('OTRA_APP_PORT', $argv[PARAMETER_PORT] ?? 8000);
define('OTRA_LIVE_APP_ENV', $argv[PARAMETER_ENV] ?? 'dev');
define('OTRA_LIVE_HTTPS', 'false');

echo CLI_GREEN, 'Launching a PHP web internal server on localhost:', OTRA_APP_PORT, ' in ',
  OTRA_LIVE_APP_ENV === 'dev' ? 'development' : 'production', ' mode.', END_COLOR, PHP_EOL;

$command = 'OTRA_LIVE_APP_ENV=' . OTRA_LIVE_APP_ENV . ' OTRA_LIVE_HTTPS=' . OTRA_LIVE_HTTPS .
  ' php -d variables_order=EGPCS -S localhost:' . OTRA_APP_PORT . ' -t ' . BASE_PATH . 'web web/index';

if (OTRA_LIVE_APP_ENV === 'dev')
  $command .= 'Dev';

// We use redirections to be able to use unit tests (stderr blocks PHPUnit execution)
$codeAndOutput = cli($command . '.php 2>&1');

// Shows errors
if ($codeAndOutput[OTRA_CLI_RETURN] !== 0)
  echo $codeAndOutput[OTRA_CLI_OUTPUT];
