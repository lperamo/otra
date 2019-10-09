#!/cygdrive/c/LPAMP/php-7.0.3/php -ddisplay_errors=E_ALL
<?
declare(strict_types=1);
use lib\myLibs\console\TasksManager;

define('BASE_PATH', str_replace('\\', '/', __DIR__) . '/');  // Fixes windows awful __DIR__. The path finishes with /
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');
$_SERVER['APP_ENV']= 'prod';

require CORE_PATH . 'console/TasksManager.php';
require CORE_PATH . 'console/Colors.php';

if (exec('whoami') === 'root')
{
  echo CLI_RED, 'You should not be root to execute this ! It will probably change the rights of your files and folders.',
  END_COLOR, PHP_EOL;
}

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
    echo CLI_RED, 'There is no description for that command !', END_COLOR;
    exit(1);
  }

  $methodDesc = $argv[1] . 'Desc';
  $paramsDesc = lib\myLibs\console\Tasks::$methodDesc();

  // We test if the number of parameters is correct
  $total = $required = 0;

  if (true === isset($paramsDesc[2]))
  {
    $result = array_count_values($paramsDesc[2]);

    // Retrieves the number of required parameters
    if (true === isset($result['required']))
      $required = $result['required'];

    // Retrieves the number of required parameters and then the final total of parameters
    $total = $required + (true === isset($result['optional']) ? $result['optional'] : 0);
  }

  if ($argc > $total + 2)
  {
    echo CLI_LIGHT_RED . 'There are too much parameters ! The total number of existing parameters is : ' . $total
      . END_COLOR . PHP_EOL . PHP_EOL;
    TasksManager::execute('help', [$_SERVER['SCRIPT_FILENAME'], 'help', $argv[1]]);
    exit(1);
  }

  if ($argc < $required + 2)
  {
    echo CLI_LIGHT_RED . 'Not enough parameters ! The total number of required parameters is : ' . $required . END_COLOR
      . PHP_EOL . PHP_EOL;
    TasksManager::execute('help', [$_SERVER['SCRIPT_FILENAME'], 'help', $argv[1]]);
    exit(1);
  }

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
    echo CLI_RED, 'There is no task named ', CLI_BROWN, $method, CLI_RED, ' !', END_COLOR, PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('> There is no task named '. CLI_WHITE . $method . CLI_BROWN .
    ' ! Do you mean ' . CLI_WHITE . $newTask . CLI_BROWN . ' ? (y/n)');

  if ('y' === $choice)
  {
    $argv[1] = $newTask;
    launchTask($argv, $argc);
  } else
    TasksManager::showCommands('This command doesn\'t exist. ');
}

?>