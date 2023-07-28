<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\convertImages;

use otra\OtraException;
use const otra\cache\php\BASE_PATH;
use const otra\cache\php\CORE_PATH;
use const otra\console\{CLI_ERROR, END_COLOR};
use function otra\tools\cliCommand;

const
  CONVERT_IMAGES_ARG_QUALITY = 4,
  CONVERT_IMAGES_ARG_SOURCE_FORMAT = 2,
  CONVERT_IMAGES_ARG_DESTINATION_FORMAT = 3,
  CONVERT_IMAGES_ARG_KEEP = 5;

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException
 * @return void
 */
function convertImages(array $argumentsVector) : void
{
  define(
    __NAMESPACE__ . '\\QUALITY',
    isset($argumentsVector[CONVERT_IMAGES_ARG_QUALITY])
      ? (int) $argumentsVector[CONVERT_IMAGES_ARG_QUALITY]
      : 75
  );

  if (QUALITY < 0 || QUALITY > 100)
  {
    echo CLI_ERROR, 'The quality must be between 0 and 100.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  define(__NAMESPACE__ . '\\SOURCE_FORMAT', $argumentsVector[CONVERT_IMAGES_ARG_SOURCE_FORMAT]);
  define(__NAMESPACE__ . '\\DESTINATION_FORMAT', $argumentsVector[CONVERT_IMAGES_ARG_DESTINATION_FORMAT]);
  define(
    __NAMESPACE__ . '\\KEEP',
    !(isset($argumentsVector[CONVERT_IMAGES_ARG_KEEP]) && $argumentsVector[CONVERT_IMAGES_ARG_KEEP] === 'false')
  );

  define(__NAMESPACE__ . '\\FILES_FORMAT', '\*.' . SOURCE_FORMAT);
    // `-xtype` checks that it is a file, and it follows symbolic links
    // `-print0` for `find` and `-0` for `parallel` allows space and new lines in file names
    // 2>&1 was to prevent PHPUnit from failing ... because it seems this command sends things on wrong std?
  define(
    __NAMESPACE__ . '\\FIND_FILES',
    'find ' . BASE_PATH . 'web/ -mindepth 2 -xtype f -name ' . FILES_FORMAT . ' -print0 '
  );

  require CORE_PATH . 'tools/cli.php';

  cliCommand(
    FIND_FILES . '| parallel -0 --eta convert -quality ' . QUALITY . '% {} {.}.' . DESTINATION_FORMAT . ' 2>&1'
  );

  if (!KEEP)
    cliCommand(FIND_FILES . '| xargs -0 rm');
}
