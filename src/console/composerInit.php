<?php
declare(strict_types=1);
namespace src\console;

use Exception;
use const otra\console\{CLI_BASE, CLI_ERROR, END_COLOR};
use function otra\console\architecture\createGlobalConstants\createGlobalConstants;
use function otra\console\architecture\init\init;
use function otra\console\deployment\buildDev\buildDev;
use function otra\console\helpAndTools\version\version;

const VENDOR_DIR = 'vendor/otra/otra/src/';
require VENDOR_DIR . 'console/architecture/createGlobalConstants/createGlobalConstantsTask.php';
require VENDOR_DIR . 'console/helpAndTools/version/versionTask.php';
require VENDOR_DIR . 'console/architecture/init/initTask.php';
require VENDOR_DIR . 'console/deployment/buildDev/buildTask.php';
require VENDOR_DIR . 'console/colors.php';

echo CLI_BASE, 'Creating global constants...', PHP_EOL;
createGlobalConstants();

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
