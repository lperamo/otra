#!/cygdrive/c/LPAMP/php-7.0.3/php -ddisplay_errors=E_ALL
<?php
declare(strict_types=1);

use lib\otra\console\TasksManager;

// TODO Centralize those constants that are already defined in AllConfig...
// Fixes windows awful __DIR__
define('_DIR_', str_replace('\\', '/', __DIR__));
define('OTRA_PROJECT', strpos(_DIR_, 'vendor') !== false); // if true, we are not developing on OTRA itself
// The path finishes with /
define(
  'BASE_PATH',
  OTRA_PROJECT === true
    ? substr(_DIR_, 0, -16) // 16 = strlen('vendor/otra/otra')
    : _DIR_ . '/'
);

define(
  'CORE_PATH',
  OTRA_PROJECT === true
    ? BASE_PATH . 'vendor/otra/otra/src/'
    : BASE_PATH . 'src/'
);
define('CACHE_PATH', BASE_PATH . 'cache/');
define('SPACE_INDENT', '  ');
$_SERVER['APP_ENV'] = 'prod';

require CORE_PATH . 'console/TasksManager.php';
require CORE_PATH . 'console/colors.php';

if (exec('whoami') === 'root')
{
  echo CLI_RED, 'You should not be root to execute this ! It will probably change the rights of your files and folders.',
  END_COLOR, PHP_EOL;
}

/**
 * Launch a task if there is a description for it and if the parameters are correctly set.
 *
 * @param array $tasksClassMap
 * @param array $argv
 * @param int   $argc
 */
function launchTask(array $tasksClassMap, array $argv, int $argc)
{
  $task = $argv[TasksManager::TASK_PARAMETERS];
  $paramsDesc = require $tasksClassMap[$task][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $task . 'Help.php';

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
    TasksManager::execute($tasksClassMap,'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $argv[1]]);
    exit(1);
  }

  if ($argc < $required + 2)
  {
    echo CLI_LIGHT_RED . 'Not enough parameters ! The total number of required parameters is : ' . $required . END_COLOR
      . PHP_EOL . PHP_EOL;
    TasksManager::execute($tasksClassMap, 'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $argv[1]]);
    exit(1);
  }

  // And we runs the task if all is correct
  TasksManager::execute($tasksClassMap, $argv[1], $argv);
}

// If we didn't specify any command, list the available commands
if ($argc < 2) {
  TasksManager::showCommands('No specified commands ! We then show the available commands ... ');
  die;
}

$tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

// if the command exists, runs it
if (true === isset($tasksClassMap[$argv[TasksManager::TASK_PARAMETERS]]))
  launchTask($tasksClassMap, $argv, $argc);
else // otherwise we'll try to guess if it looks like an existing one
{
  $methods = array_keys($tasksClassMap);

  require CORE_PATH . 'console/tools.php';
  $method = $argv[TasksManager::TASK_PARAMETERS];
  list($newTask) = guessWords($method, $methods);

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo CLI_RED, 'There is no task named ', CLI_YELLOW, $method, CLI_RED, ' !', END_COLOR, PHP_EOL;
    exit(1);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('> There is no task named '. CLI_WHITE . $method . CLI_YELLOW .
    ' ! Do you mean ' . CLI_WHITE . $newTask . CLI_YELLOW . ' ? (y/n)');

  if ('y' === $choice)
  {
    $argv[1] = $newTask;
    launchTask($tasksClassMap, $argv, $argc);
  } else
    TasksManager::showCommands('This command doesn\'t exist. ');
}

?>
