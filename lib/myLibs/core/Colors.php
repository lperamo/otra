<? function brown(){ return "\033[0;33m"; }function cyan(){ return "\033[0;36m"; }function lightBlue(){ return "\033[1;34m"; }function lightCyan(){ return "\033[1;36m"; }function lightGray(){ return "\033[0;37m"; }function lightGreen(){ return "\033[1;32m"; }function lightRed(){ return "\033[1;31m"; }function red(){ return "\033[0;31m"; }function green(){ return "\033[0;32m"; }function white(){ return "\033[1;37m"; }function yellow(){ return "\033[1;33m"; }function endColor(){ return "\033[0m"; }function dieC($color, $message){ die($color() . $message . endColor()); } ?>