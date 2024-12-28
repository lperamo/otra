<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
use const otra\console\{CLI_WARNING, END_COLOR};
const ANNOTATION_DEBUG_PAD = 80;
/**
 * Shows the file name in the console for debug purposes
 */
function showFile(int $level, string $fileAbsolutePath, string $otherText = ' first file') : void
{
  if (VERBOSE > 0)
    echo str_pad(
      str_repeat(' ', $level << 1) . (0 !== $level ? '| ' : '') .
      mb_substr($fileAbsolutePath, BASE_PATH_LENGTH),
      ANNOTATION_DEBUG_PAD,
      '.'
    ), CLI_WARNING, $otherText, END_COLOR, PHP_EOL;
}
