<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

define('BLUE_ON_LIGHT_BLACK', CLI_BLUE . CLI_BGD_LIGHT_BLACK);
define('LIGHTBLUE_ON_LIGHT_BLACK', CLI_LIGHT_BLUE . CLI_BGD_LIGHT_BLACK);
define('END_PADDING', 21);
define('INITIAL_ADDITIONAL_PADDING', 39);

echo CLI_BGD_LIGHT_BLACK, str_repeat(' ', END_PADDING + INITIAL_ADDITIONAL_PADDING), "\n" .
  BLUE_ON_LIGHT_BLACK, " ..|''||   ", LIGHTBLUE_ON_LIGHT_BLACK, "|''||''| ", BLUE_ON_LIGHT_BLACK, "  '''|.   ", LIGHTBLUE_ON_LIGHT_BLACK, "    |    ", str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK, ".|'    ||  ", LIGHTBLUE_ON_LIGHT_BLACK, " ' || '  ", BLUE_ON_LIGHT_BLACK, " ||   ||  ", LIGHTBLUE_ON_LIGHT_BLACK, "   |||   ", str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK, "||      || ", LIGHTBLUE_ON_LIGHT_BLACK, "   ||    ", BLUE_ON_LIGHT_BLACK, "'||''|'   ", LIGHTBLUE_ON_LIGHT_BLACK, "  |  .|  ", str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK, "'|.     || ", LIGHTBLUE_ON_LIGHT_BLACK, "   ||    ", BLUE_ON_LIGHT_BLACK, " ||   |.  ", LIGHTBLUE_ON_LIGHT_BLACK, " |''''|. ", str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK, " ''|...|'  ", LIGHTBLUE_ON_LIGHT_BLACK, "  .||.   ", BLUE_ON_LIGHT_BLACK, ".||.  '|' ", LIGHTBLUE_ON_LIGHT_BLACK, ".'    '|'", str_repeat(' ', END_PADDING) . "
                                                            
                       ";
$byPeramoLionel = explode('*', "B*y* *P*é*r*a*m*o* *L*i*o*n*e*l*.");

foreach($byPeramoLionel as $index => $character)
{
  $keyTwice = $index << 2;
  echo "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" . $character;
}

echo str_repeat(' ', 20), PHP_EOL,
  str_repeat(' ', 60), PHP_EOL,
  str_repeat(' ', 40), CLI_WHITE, CLI_BGD_LIGHT_BLACK, OTRA_VERSION, END_COLOR, PHP_EOL;

