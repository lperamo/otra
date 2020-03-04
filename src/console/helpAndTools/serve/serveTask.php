<?php
require CORE_PATH . 'tools/cli.php';
define('PARAMETER_PORT', 2);
define('PARAMETER_ENV', 3);
$port = isset($argv[PARAMETER_PORT]) ? $argv[PARAMETER_PORT] : 8000;
$env = isset($argv[PARAMETER_ENV]) ? $argv[PARAMETER_ENV] : 'dev';

echo CLI_GREEN, 'Launching a PHP web internal server on localhost:', $port, ' in ',
  $env === 'dev' ? 'development' : 'production', ' mode.', END_COLOR, PHP_EOL;

$command = 'OTRA_APP_ENV=' . $env . ' php -d variables_order=EGPCS -S localhost:' . $port . ' -t web web/index';

if(PARAMETER_ENV === 'dev')
  $command .= 'Dev';

cli($command . '.php');
