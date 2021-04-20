<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */
const CLI_BGD_LIGHT_BLACK="\e[48;2;40;40;40m",
  CLI_INFO_GREEN="\e[38;2;185;215;255m",
  CLI_VERSION_COLOR="\e[38;2;220;220;220m",
  BLUE_ON_LIGHT_BLACK = "\e[38;2;140;170;255m" . CLI_BGD_LIGHT_BLACK,
  LIGHTBLUE_ON_LIGHT_BLACK = CLI_INFO_GREEN . CLI_BGD_LIGHT_BLACK,
  END_PADDING = 21,
  INITIAL_ADDITIONAL_PADDING = 39;

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
  echo "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" .
    $character;
}

echo str_repeat(' ', 20), PHP_EOL,
  str_repeat(' ', 60), PHP_EOL,
  str_repeat(' ', 40), CLI_VERSION_COLOR, CLI_BGD_LIGHT_BLACK, OTRA_VERSION, END_COLOR, PHP_EOL;

//throw new \otra\OtraException('test');
