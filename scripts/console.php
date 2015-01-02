#!/usr/bin/php -ddisplay_errors=E_ALL
<?
define('_DIR_', str_replace('\\', '/', __DIR__));  // Fixes windows awful __DIR__
define('XMODE', 'dev');
define('ROOTPATH', _DIR_ . '/../'); // CHECK ALL OCCURRENCES OF THIS AND SUPPRESS THEM
define('BASE_PATH', substr(_DIR_, 0, -7)); // Finit avec /

require BASE_PATH . 'lib/myLibs/core/Tasks_Manager.php';
require BASE_PATH . 'lib/myLibs/core/Colors.php';

// If we didn't specify any command, list the available commands
if ($argc < 2) {
  lib\myLibs\core\Tasks_Manager::showCommands('No commands specified ! ');
  die;
}

// if the command exists, runs it
if (method_exists('lib\myLibs\core\Tasks', $argv[1]) && method_exists('lib\myLibs\core\Tasks', $argv[1] . 'Desc'))
{
  $methodDesc = $argv[1] . 'Desc';
  $paramsDesc = lib\myLibs\core\Tasks::$methodDesc();

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
  lib\myLibs\core\Tasks_Manager::execute($argv[1], $argv);
} else
  lib\myLibs\core\Tasks_Manager::showCommands('This command doesn\'t exist. ');
?>
