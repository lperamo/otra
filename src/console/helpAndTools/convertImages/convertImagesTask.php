<?php
declare(strict_types=1);
namespace src\console\helpAndTools\convertImages;

use otra\OtraException;
use const otra\cache\php\BASE_PATH;
use const otra\cache\php\CORE_PATH;
use const otra\console\{CLI_ERROR, END_COLOR};
use function otra\tools\cliCommand;

const CONVERT_IMAGES_ARG_QUALITY = 4;

define(
  __NAMESPACE__ . '\\QUALITY',
  isset($argv[CONVERT_IMAGES_ARG_QUALITY])
    ? (int) $argv[CONVERT_IMAGES_ARG_QUALITY]
    : 75
);

if (QUALITY < 0 || QUALITY > 100)
{
  echo CLI_ERROR, 'The quality must be between 0 and 100.', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

const
  CONVERT_IMAGES_ARG_SOURCE_FORMAT = 2,
  CONVERT_IMAGES_ARG_DESTINATION_FORMAT = 3,
  CONVERT_IMAGES_ARG_KEEP = 5;

define(__NAMESPACE__ . '\\SOURCE_FORMAT', $argv[CONVERT_IMAGES_ARG_SOURCE_FORMAT]);
define(__NAMESPACE__ . '\\DESTINATION_FORMAT', $argv[CONVERT_IMAGES_ARG_DESTINATION_FORMAT]);
define(
  __NAMESPACE__ . '\\KEEP',
  !(isset($argv[CONVERT_IMAGES_ARG_KEEP]) && $argv[CONVERT_IMAGES_ARG_KEEP] === 'false')
);

require CORE_PATH . 'tools/cli.php';
const
  FILES_FORMAT = '\*.' . SOURCE_FORMAT,
  // `-xtype` checks that it is a file and it follows symbolic links
  // `-print0` for `find` and `-0` for `parallel` allows space and new lines in file names
  // 2>&1 was to prevent PHPUnit from failing ... because it seems this command sends things on wrong std?
  FIND_FILES = 'find ' . BASE_PATH . 'web/ -mindepth 2 -xtype f -name ' . FILES_FORMAT . ' -print0 ';

cliCommand(
  FIND_FILES . '| parallel -0 --eta convert -quality ' . QUALITY . '% {} {.}.' . DESTINATION_FORMAT . ' 2>&1'
);

if (!KEEP)
  cliCommand(FIND_FILES . '| xargs -0 rm');
