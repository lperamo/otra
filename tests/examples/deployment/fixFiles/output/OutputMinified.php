<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;const ADD_BOLD="\e[1m",REMOVE_BOLD_INTENSITY="\e[22m",ADD_UNDERLINE="\e[4m",REMOVE_UNDERLINE="\e[24m",END_COLOR="\e[0m",CLI_BASE="\e[38;2;190;190;190m",CLI_SUCCESS="\e[38;2;100;200;100m",CLI_INFO="\e[38;2;100;150;200m",CLI_ERROR="\e[38;2;255;100;100m",CLI_INFO_HIGHLIGHT="\e[38;2;100;200;200m",CLI_TABLE="\e[38;2;100;180;255m",CLI_TABLE_HEADER="\e[38;2;75;100;255m",CLI_WARNING="\e[38;2;190;190;100m",CLI_GRAY="\e[38;2;160;160;160m",CLI_INDENT_COLOR_FIRST=ADD_BOLD.CLI_INFO,CLI_INDENT_COLOR_SECOND=ADD_BOLD.CLI_ERROR,CLI_INDENT_COLOR_FOURTH=ADD_BOLD.CLI_INFO_HIGHLIGHT,CLI_INDENT_COLOR_FIFTH="\e[38;2;150;0;255m",CLI_DUMP_LINE_HIGHLIGHT="\e[38;2;140;200;255m",CLI_LINE_DUMP="\e[38;2;83;148;236m",ERASE_SEQUENCE="\033[1A\r\033[K",SUCCESS=CLI_SUCCESS.' ✔'.END_COLOR.PHP_EOL;class TestMinified
{
  public static function run(): void
  {
    /** @author Lionel Péramo @package otra\console */
  }
}
