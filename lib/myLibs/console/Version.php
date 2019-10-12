<?php

define('BLUE_ON_LIGHT_BLACK', CLI_BLUE . CLI_BGD_LIGHT_BLACK);
define('LIGHTBLUE_ON_LIGHT_BLACK', CLI_LIGHT_BLUE . CLI_BGD_LIGHT_BLACK);
define('END_PADDING', 21);
/**
 * @param bool $light
 *
 * @return string
 */
function blue(bool $light = true) : string
{
  return CLI_BLUE . CLI_BGD_LIGHT_BLACK;
}

echo CLI_BGD_LIGHT_BLACK . str_repeat(' ', END_PADDING + 39) . "\n" .
  BLUE_ON_LIGHT_BLACK . " ..|''||   " . LIGHTBLUE_ON_LIGHT_BLACK . "|''||''| " . BLUE_ON_LIGHT_BLACK . "  '''|.   " . LIGHTBLUE_ON_LIGHT_BLACK . "    |    " . str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK . ".|'    ||  " . LIGHTBLUE_ON_LIGHT_BLACK . " ' || '  " . BLUE_ON_LIGHT_BLACK . " ||   ||  " . LIGHTBLUE_ON_LIGHT_BLACK . "   |||   " . str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK . "||      || " . LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . BLUE_ON_LIGHT_BLACK . "'||''|'   " . LIGHTBLUE_ON_LIGHT_BLACK . "  |  .|  " . str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK . "'|.     || " . LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . BLUE_ON_LIGHT_BLACK . " ||   |.  " . LIGHTBLUE_ON_LIGHT_BLACK . " |''''|. " . str_repeat(' ', END_PADDING) .
  PHP_EOL
. BLUE_ON_LIGHT_BLACK . " ''|...|'  " . LIGHTBLUE_ON_LIGHT_BLACK . "  .||.   " . BLUE_ON_LIGHT_BLACK . ".||.  '|' " . LIGHTBLUE_ON_LIGHT_BLACK . ".'    '|'" . str_repeat(' ', END_PADDING) . "
                                                            
                       By Péramo Lionel.                    
                                                            
                                        " . CLI_WHITE . CLI_BGD_LIGHT_BLACK  . 'Version 1.0.0-alpha.' . END_COLOR . PHP_EOL;
?>