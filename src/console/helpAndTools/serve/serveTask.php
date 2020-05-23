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
  ' OTRA_LIVE_OTRA_HASH=\$2y\$07\$hu3yJ9cEtjFXwzpHoMdv5n' .
  ' php -d variables_order=EGPCS -S localhost:' . OTRA_APP_PORT . ' -t web web/index';

if (OTRA_LIVE_APP_ENV === 'dev')
  $command .= 'Dev';

cli($command . '.php');
