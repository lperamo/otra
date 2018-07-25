#!/cygdrive/c/LPAMP/php-7.0.3/php -ddisplay_errors=E_ALL
<?
declare(strict_types=1);
use lib\myLibs\console\TasksManager;

// Workaround for Babun on Windows otherwise Babun doesn't find PHP !
if (true === isset($_SERVER['_']) && false !== strpos($_SERVER['_'], 'cygdrive')
  || false === isset($_SERVER['_']))
  $_SERVER['_'] = $_SERVER['PHP_PEAR_PHP_BIN'];

define('BASE_PATH', str_replace('\\', '/', __DIR__) . '/');  // Fixes windows awful __DIR__. The path finishes with /
define('XMODE', 'dev');
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');

require CORE_PATH . 'console/TasksManager.php';
require CORE_PATH . 'console/Colors.php';

/**
 * Launch a task if there is a description for it and if the parameters are correctly set.
 *
 * @param array $argv
 * @param int   $argc
 */
function launchTask(array $argv, int $argc)
{
  // Checks if a task description is missing
  if (false === method_exists('lib\myLibs\console\Tasks', $argv[1] . 'Desc'))
  {
    echo redText('There is no description for that command !');
    exit(1);
  }

  $methodDesc = $argv[1] . 'Desc';
  $paramsDesc = lib\myLibs\console\Tasks::$methodDesc();

  // We test if the number of parameters is correct
  $total = $required = 0;

  if (true === isset($paramsDesc[2]))
  {
    $result = array_count_values($paramsDesc[2]);

    if (true === isset($result['required']))
      $required = $result['required'];

    $total = $required + (true === isset($result['optional']) ? $result['optional'] : 0);
  }

  if ($argc > $total + 2)
    dieC('lightRed', 'There are too much parameters ! The total number of existing parameters is : ' . $total . PHP_EOL);

  if ($argc < $required + 2)
    dieC('lightRed', 'Not enough parameters ! The total number of required parameters is : ' . $required . PHP_EOL);

  // And we runs the task if all is correct
  TasksManager::execute($argv[1], $argv);
}

// If we didn't specify any command, list the available commands
if ($argc < 2) {
  TasksManager::showCommands('No specified commands ! We then show the available commands ... ');
  die;
}

// if the command exists, runs it
if (true === method_exists('lib\myLibs\console\Tasks', $argv[1]))
  launchTask($argv, $argc);
else // otherwise we'll try to guess if it looks like an existing one
{
  $methods = get_class_methods('lib\myLibs\console\Tasks');

  // We filter the 'description' methods from the class methods
  foreach($methods as $key => &$method)
  {
    if (false !== strpos($method, 'Desc'))
      unset($methods[$key]);
  }

  require CORE_PATH . 'console/Tools.php';
  $method = $argv[1];
  list($newTask) = guessWords($method, $methods);

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo red(), 'There is no task named ', brown(), $method, redText(' !'), PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('> There is no task named ' . $method . ' ! Do you mean ' . lightBlue() . $newTask . brown() . ' ? (y/n)');

  if ('y' === $choice)
  {
    $argv[1] = $newTask;
    launchTask($argv, $argc);
  } else
    TasksManager::showCommands('This command doesn\'t exist. ');
}

?>

