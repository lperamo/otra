<?php
declare(strict_types=1);
namespace src\console;

use Exception;
use const otra\cache\php\CACHE_PATH;
use const otra\console\{CLI_BASE, CLI_ERROR, END_COLOR};
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
require VENDOR_DIR . 'console/deployment/buildDev/buildDevTask.php';

try
{
  version();
  init();
  echo CLI_BASE, 'Building assets...', END_COLOR, PHP_EOL;
  buildDev(['bin/otra.php', 'buildDev', '0', '7', 'true', '1']);
} catch (Exception $exception)
{
  echo CLI_ERROR, $exception->getTraceAsString(), END_COLOR, PHP_EOL;
}
