#!/cygdrive/c/LPAMP/php-7.0.3/php -ddisplay_errors=E_ALL
<?php
declare(strict_types=1);

use otra\console\TasksManager;
use otra\OtraException;

define('OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
require __DIR__ . (OTRA_PROJECT
    ? '/../../../..' // long path from vendor
    : '/..'
  ) . '/config/constants.php';
const CACHE_PHP_INIT_PATH = CACHE_PATH . 'php/init/';
$_SERVER[APP_ENV] = PROD;
require CONSOLE_PATH . 'TasksManager.php';
require CONSOLE_PATH . 'colors.php';

// We check if the help and task class map is present, if not ... generate it.
// In fact, we also generate shell completions... for now.
if (!file_exists(CACHE_PHP_INIT_PATH . 'tasksHelp.php'))
{
  echo 'Some needed files are missing ... We are going to fix that !', PHP_EOL;
  require CONSOLE_PATH . 'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';
  echo 'Now we can continue as planned.', PHP_EOL;
}

if (exec('whoami') === 'root')
{
  echo CLI_ERROR, 'You should not be root to execute this ! It will probably change the rights of your files and folders.',
    END_COLOR, PHP_EOL;
}

/**
 * Launch a task if there is a description for it and if the parameters are correctly set.
 *
 * @param array $tasksClassMap
 * @param array $arguments
 * @param int   $argumentsCount
 *
 * @throws OtraException
 */
function launchTask(array $tasksClassMap, array $arguments, int $argumentsCount) : void
{
  $taskName = $arguments[TasksManager::TASK_PARAMETERS];
  $paramsDesc = require $tasksClassMap[$taskName][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $taskName . 'Help.php';

  // We test if the number of parameters is correct
  $total = $required = 0;

  if (isset($paramsDesc[2]))
  {
    $result = array_count_values($paramsDesc[2]);

    // Retrieves the number of required parameters
    if (isset($result['required']))
      $required = $result['required'];

    // Retrieves the number of required parameters and then the final total of parameters
    $total = $required + ($result['optional'] ?? 0);
  }

  if ($argumentsCount > $total + 2)
  {
    echo CLI_ERROR . 'There are too much parameters ! The total number of existing parameters is : ' . $total
      . END_COLOR . PHP_EOL . PHP_EOL;
    TasksManager::execute($tasksClassMap,'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $arguments[1]]);
    throw new OtraException('', 1, '', NULL, [], true);
  }

  if ($argumentsCount < $required + 2)
  {
    echo CLI_ERROR . 'Not enough parameters ! The total number of required parameters is : ' . $required . END_COLOR
      . PHP_EOL . PHP_EOL;
    TasksManager::execute($tasksClassMap, 'help', [$_SERVER['SCRIPT_FILENAME'], 'help', $arguments[1]]);
    throw new OtraException('', 1, '', NULL, [], true);
  }

  // And we runs the task if all is correct
  TasksManager::execute($tasksClassMap, $arguments[1], $arguments);
}

ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED);
require CORE_PATH . 'OtraException.php';
set_error_handler([OtraException::class, 'errorHandler']);
set_exception_handler([OtraException::class, 'exceptionHandler']);
spl_autoload_register(function(string $className) : void { require CLASSMAP[$className]; });

// If we didn't specify any command, list the available commands
if ($argc < 2)
{
  TasksManager::showCommands('No specified commands ! We then show the available commands ... ');
  throw new OtraException('', 1, '', NULL, [], true);
}

$tasksClassMap = require CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

// if the command exists, runs it
if (isset($tasksClassMap[$argv[TasksManager::TASK_PARAMETERS]]))
  launchTask($tasksClassMap, $argv, $argc);
else // otherwise we'll try to guess if it looks like an existing one
{
  $methods = array_keys($tasksClassMap);

  require CONSOLE_PATH . 'tools.php';
  $method = $argv[TasksManager::TASK_PARAMETERS];
  [$newTask] = guessWords($method, $methods);

  // If there are no existing task with a close name ...
  if (null === $newTask)
  {
    echo CLI_ERROR, 'There is no task named ', CLI_WARNING, $method, CLI_ERROR, ' !', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  // Otherwise, we suggest the closest name that we have found.
  $choice = promptUser('> There is no task named '. CLI_BASE . $method . CLI_WARNING .
    ' ! Do you mean ' . CLI_BASE . $newTask . CLI_WARNING . ' ? (y/n)');

  if ('y' === $choice)
  {
    $argv[1] = $newTask;
    launchTask($tasksClassMap, $argv, $argc);
  } else
    TasksManager::showCommands('This command does not exist. ');
}
