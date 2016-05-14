#!/usr/bin/php -ddisplay_errors=E_ALL
<?
declare(strict_types=1);
define('BASE_PATH', str_replace('\\', '/', __DIR__) . '/');  // Fixes windows awful __DIR__. The path finishes with /
define('XMODE', 'dev');
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');

require CORE_PATH . 'console/TasksManager.php';
require CORE_PATH . 'console/Colors.php';

// If we didn't specify any command, list the available commands
if ($argc < 2) {
  lib\myLibs\console\TasksManager::showCommands('No specified commands ! We then show the available commands ... ');
  die;
}

// if the command exists, runs it
if (method_exists('lib\myLibs\console\Tasks', $argv[1]) && method_exists('lib\myLibs\console\Tasks', $argv[1] . 'Desc'))
{
  $methodDesc = $argv[1] . 'Desc';
  $paramsDesc = lib\myLibs\console\Tasks::$methodDesc();

    // We test if the number of parameters is correct
  $total = $required = 0;
  if(isset($paramsDesc[2]))
  {
    $result = array_count_values($paramsDesc[2]);

    if(isset($result['required']))
      $required = $result['required'];

    $total = $required + (isset($result['optional']) ? $result['optional'] : 0);
  }

  if($argc > $total + 2)
    dieC('lightRed', 'There are too much parameters ! The total number of existing parameters is : ' . $total . PHP_EOL);

  if($argc < $required + 2)
    dieC('lightRed', 'Not enough parameters ! The total number of required parameters is : ' . $required . PHP_EOL);

  // And we runs the task if all is correct
  lib\myLibs\console\TasksManager::execute($argv[1], $argv);
} else
  lib\myLibs\console\TasksManager::showCommands('This command doesn\'t exist. ');
?>
