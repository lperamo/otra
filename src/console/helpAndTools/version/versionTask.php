<?php

define('BLUE_ON_LIGHT_BLACK', CLI_BLUE . CLI_BGD_LIGHT_BLACK);
define('LIGHTBLUE_ON_LIGHT_BLACK', CLI_LIGHT_BLUE . CLI_BGD_LIGHT_BLACK);
define('END_PADDING', 21);

echo CLI_BGD_LIGHT_BLACK, str_repeat(' ', END_PADDING + 39), "\n" .
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
$by = explode('*', "B*y* *P*é*r*a*m*o* *L*i*o*n*e*l*.");

foreach($by as $key => &$character)
{
  $keyTwice = $key << 2;
  echo "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" . $character;
}

echo str_repeat(' ', 20), PHP_EOL,
  str_repeat(' ', 60), PHP_EOL,
  str_repeat(' ', 40), CLI_WHITE, CLI_BGD_LIGHT_BLACK, 'Version 1.0.0-alpha.2.0.1', END_COLOR, PHP_EOL;
?>
