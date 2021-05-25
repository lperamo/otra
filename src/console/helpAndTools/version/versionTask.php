<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\version;

use function otra\tools\getOtraCommitNumber;
use const otra\cache\php\{CORE_PATH, OTRA_VERSION};
use const otra\console\END_COLOR;

const
  CLI_BGD_LIGHT_BLACK="\e[48;2;40;40;40m",
  CLI_INFO_GREEN="\e[38;2;185;215;255m",
  CLI_VERSION_COLOR="\e[38;2;220;220;220m",
  BLUE_ON_LIGHT_BLACK = "\e[38;2;140;170;255m" . CLI_BGD_LIGHT_BLACK,
  LIGHTBLUE_ON_LIGHT_BLACK = CLI_INFO_GREEN . CLI_BGD_LIGHT_BLACK,
  END_PADDING = 10,
  COMMIT_LENGTH = 14,
  SPACE_BEFORE_COPYRIGHT = 22, // 39 - 17
  TOTAL_WIDTH = 39,
  TOTAL_PLUS_END_PADDING = TOTAL_WIDTH + END_PADDING;

define('otra\console\helpAndTools\version\END_PADDING_STRING', str_repeat(' ', END_PADDING));
define('otra\console\helpAndTools\version\BLANK_LINE', str_repeat(' ', TOTAL_PLUS_END_PADDING));

echo CLI_BGD_LIGHT_BLACK, BLANK_LINE, PHP_EOL .
  BLUE_ON_LIGHT_BLACK, " ..|''||   ", LIGHTBLUE_ON_LIGHT_BLACK, "|''||''| ", BLUE_ON_LIGHT_BLACK, "  '''|.   ", LIGHTBLUE_ON_LIGHT_BLACK, "    |    ", END_PADDING_STRING . PHP_EOL
. BLUE_ON_LIGHT_BLACK, ".|'    ||  ", LIGHTBLUE_ON_LIGHT_BLACK, " ' || '  ", BLUE_ON_LIGHT_BLACK, " ||   ||  ", LIGHTBLUE_ON_LIGHT_BLACK, "   |||   ", END_PADDING_STRING . PHP_EOL
. BLUE_ON_LIGHT_BLACK, "||      || ", LIGHTBLUE_ON_LIGHT_BLACK, "   ||    ", BLUE_ON_LIGHT_BLACK, "'||''|'   ", LIGHTBLUE_ON_LIGHT_BLACK, "  |  .|  ", END_PADDING_STRING . PHP_EOL
. BLUE_ON_LIGHT_BLACK, "'|.     || ", LIGHTBLUE_ON_LIGHT_BLACK, "   ||    ", BLUE_ON_LIGHT_BLACK, " ||   |.  ", LIGHTBLUE_ON_LIGHT_BLACK, " |''''|. ", END_PADDING_STRING . PHP_EOL
. BLUE_ON_LIGHT_BLACK, " ''|...|'  ", LIGHTBLUE_ON_LIGHT_BLACK, "  .||.   ", BLUE_ON_LIGHT_BLACK, ".||.  '|' ", LIGHTBLUE_ON_LIGHT_BLACK, ".'    '|'", END_PADDING_STRING . PHP_EOL .
  BLANK_LINE . PHP_EOL .
  str_repeat(' ', SPACE_BEFORE_COPYRIGHT);
$byPeramoLionel = explode('*', "B*y* *P*é*r*a*m*o* *L*i*o*n*e*l*.");

foreach($byPeramoLionel as $index => $character)
{
  $keyTwice = $index << 2;
  echo "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" .
    $character;
}

// We enforce black background by putting black spaces
echo END_PADDING_STRING, PHP_EOL,
  BLANK_LINE, PHP_EOL;

require CORE_PATH . 'tools/getOtraCommitNumber.php';

echo CLI_VERSION_COLOR, CLI_BGD_LIGHT_BLACK, 'Commit ', getOtraCommitNumber(true, true),
  str_pad(OTRA_VERSION, TOTAL_WIDTH - COMMIT_LENGTH, ' ', STR_PAD_LEFT),
  END_PADDING_STRING, PHP_EOL,
  CLI_BGD_LIGHT_BLACK, str_repeat(' ', TOTAL_PLUS_END_PADDING), END_COLOR,  PHP_EOL;
