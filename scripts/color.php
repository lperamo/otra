<?php
function colorize($text, $status) {
 $out = "";
 switch($status) {
  case "SUCCESS":
   $out = "[42m"; //Green background
   break;
  case "FAILURE":
   $out = "[41m"; //Red background
   break;
  case "WARNING":
   $out = "[43m"; //Yellow background
   break;
  case "NOTE":
   $out = "[44m"; //Blue background
   break;
  default:
   throw new Exception("Invalid status: " . $status);
 }
 return chr(27) . "$out" . "$text" . chr(27) . "[0m";
}

require_once("ANSIColor.php");
$ac = new ANSIColor();
echo $ac->bold_blue("Text\n");

// echo colorize("Your command has successfully executed...", "SUCCESS");
// echo PHP_EOL.chr(33).'[21mtest'.chr(33).'[0m';
?>