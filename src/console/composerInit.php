<?php
declare(strict_types=1);
namespace src\console;

use Exception;
use const otra\cache\php\CACHE_PATH;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\architecture\createGlobalConstants\createGlobalConstants;
use function otra\console\architecture\init\init;
use function otra\console\deployment\buildDev\buildDev;
use function otra\console\helpAndTools\version\version;

const VENDOR_DIR = 'vendor/otra/otra/src/';
require VENDOR_DIR . 'console/architecture/createGlobalConstants/createGlobalConstantsTask.php';
require VENDOR_DIR . 'console/colors.php';

echo CLI_BASE, 'Creating global constants...', PHP_EOL;
createGlobalConstants();
// loads the created constants
error_reporting(E_ALL ^ E_WARNING);
require __DIR__ . '/../../../../../config/constants.php';
error_reporting(E_ALL);
define('otra\\bin\\CACHE_PHP_INIT_PATH', CACHE_PATH . 'php/init/');

require VENDOR_DIR . 'console/helpAndTools/version/versionTask.php';
require VENDOR_DIR . 'console/architecture/init/initTask.php';

try
{
  version();
  init();
  echo 'Configure the path in the variable ', CLI_INFO_HIGHLIGHT, '$nodeBinariesPath', CLI_BASE, ' in ',
    CLI_INFO_HIGHLIGHT, 'config/dev/AllConfig.php', CLI_BASE, ' and ', CLI_INFO_HIGHLIGHT, 'config/prod/AllConfig.php',
    CLI_BASE, '.', PHP_EOL, 'Then launch ', CLI_INFO_HIGHLIGHT, 'otra buildDev 0 7 true 1', CLI_BASE, '.', PHP_EOL;
  echo 'If you are on some unix distribution, you can add the following line to your profile to have a shortcut to OTRA binary',
  PHP_EOL;
  echo CLI_INFO_HIGHLIGHT, 'alias otra="php bin/otra.php"', END_COLOR, PHP_EOL, PHP_EOL;
  echo 'If you want to see an example application, type ', CLI_INFO_HIGHLIGHT, 'otra createHelloWorld'. END_COLOR, '.',
    PHP_EOL;
} catch (Exception $exception)
{
  echo CLI_ERROR, $exception->getTraceAsString(), END_COLOR, PHP_EOL;
}
