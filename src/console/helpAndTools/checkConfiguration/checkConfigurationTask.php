<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\checkConfiguration;

use FilesystemIterator;
use otra\OtraException;
use const otra\cache\php\BUNDLES_PATH;
use const otra\console\{CLI_ERROR, END_COLOR};

if (!file_exists(BUNDLES_PATH) || !(new FilesystemIterator(BUNDLES_PATH))->valid())
{
  echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

echo 'Checking routes configuration...' . PHP_EOL;
