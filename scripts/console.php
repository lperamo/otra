#!/usr/bin/php
<?

define('XMODE', 'dev');
require('../lib/myLibs/core/Tasks_Manager.php');

echo PHP_EOL; // for more lisibility
// If we didn't specify any command, list the available commands
if ($argc < 2){
  lib\myLibs\core\Tasks_Manager::showCommands('No commands specified ! ');
  die;
}

// if the command exists, runs it
if (method_exists('lib\myLibs\core\Tasks', $argv[1]))
{
  $methodDesc = $argv[1] . 'Desc';
  $paramsDesc = lib\myLibs\core\Tasks::$methodDesc();

    // We test if the number of parameters is correct
  $total = $required = 0;
  if(isset($paramsDesc[2]))
  {
    $result = array_count_values($paramsDesc[2]);
    $required = $result['required'];
    $total = $required + $result['optional'];
  }

  if($argc > $total + 2)
    die('There are too much parameters ! The total number of existing parameters is : ' . $total . PHP_EOL);
  elseif($argc < $required + 2)
    die('Not enough parameters ! The total number of required parameters is : ' . $required . PHP_EOL);

  // And we runs the task if all is correct
  lib\myLibs\core\Tasks_Manager::execute($argv[1], $argv);
}else
  lib\myLibs\core\Tasks_Manager::showCommands('This command doesn\'t exist. ');
?>
