#!/usr/bin/php -ddisplay_errors=E_ALL
<?php
declare(strict_types=1);
namespace otra\config
{
  defined('otra\\cache\\php\\OTRA_PROJECT') ||
    define('otra\\cache\\php\\OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
}

namespace otra\bin
{
  use otra\{console\TasksManager, OtraException};
  use const otra\cache\php\CLASSMAP;
  use const otra\cache\php\{APP_ENV, CACHE_PATH, CONSOLE_PATH, CORE_PATH, OTRA_PROJECT, PROD};
  use const otra\console\{CLI_BASE, CLI_ERROR, CLI_WARNING, END_COLOR};
  use function otra\console\{guessWords, launchTask, launchTaskPosixWay, promptUser};
  use function otra\console\helpAndTools\generateTaskMetadata\generateTaskMetadata;

  require __DIR__ . (OTRA_PROJECT
      ? '/../../../..' // long path from vendor
      : '/..'
    ) . '/config/constants.php';
  const CACHE_PHP_INIT_PATH = CACHE_PATH . 'php/init/';
  $_SERVER[APP_ENV] = PROD;
  require CONSOLE_PATH . 'TasksManager.php';
  require CONSOLE_PATH . 'colors.php';

  // We check if the help and task class map are present, if not ... generate it.
  // In fact, we also generate shell completions... for now.
  if (!file_exists(CACHE_PHP_INIT_PATH . 'tasksHelp.php'))
  {
    echo 'Some needed files are missing ... We are going to fix that !', PHP_EOL;
    require CONSOLE_PATH . 'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';
    generateTaskMetadata();
    echo 'Now we can continue as planned.', PHP_EOL;
  }

  if (exec('whoami') === 'root')
  {
    echo CLI_ERROR,
      'You should not be root to execute this! It will probably change the rights of your files and folders.',
      END_COLOR, PHP_EOL;
  }

  // If the class map already exists, we load it right away to better handle errors
  if (file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
    require_once CACHE_PHP_INIT_PATH . 'ClassMap.php';

  // Error handling
  ini_set('display_errors', '1');
  error_reporting(E_ALL);
  require CORE_PATH . 'OtraException.php';
  set_error_handler(OtraException::errorHandler(...));
  set_exception_handler(OtraException::exceptionHandler(...));
  spl_autoload_register(function (string $className) : void
  {
    require CLASSMAP[$className];
  });

  // If we didn't specify any command, list the available commands
  if ($argc < 2)
  {
    TasksManager::showCommands('No specified commands ! We then show the available commands ... ');
    throw new OtraException(code: 1, exit: true);
  }

  $argumentsVector = $argv;
  $tasksClassMap = require CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

  if ($argumentsVector[TasksManager::TASK_NAME][0] === '-')
  {
    define(__NAMESPACE__ . '\\POSIX_MODE', true);
    $taskName = getopt('t:')['t'];
    $launchArgs = [$tasksClassMap, $argumentsVector, $taskName];
  } else
  {
    define(__NAMESPACE__ . '\\POSIX_MODE', false);
    $taskName = $argumentsVector[TasksManager::TASK_NAME];
    $launchArgs = [$tasksClassMap, $argumentsVector, $argc, $taskName];
  }

  $launchCallback = POSIX_MODE ? 'launchTaskPosixWay' : 'launchTask';
  require CONSOLE_PATH . $launchCallback . '.php';
  $launchCallback = 'otra\\console\\' . $launchCallback;

  // if the command exists, runs it
  if (isset($tasksClassMap[$taskName]))
    $launchCallback(...$launchArgs);
  else // otherwise, we'll try to guess if it looks like an existing one
  {
     $tasks = array_keys($tasksClassMap);

    require CONSOLE_PATH . 'tools.php';
    [$newTask] = guessWords($taskName,  $tasks);

    // If there is no existing task with a close name ...
    if (null === $newTask)
    {
      echo CLI_ERROR, 'There is no task named ', CLI_WARNING, $taskName, CLI_ERROR, ' !', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    // Otherwise, we suggest the closest name that we have found.
    $choice = promptUser('> There is no task named ' . CLI_BASE . $taskName . CLI_WARNING .
      ' ! Do you mean ' . CLI_BASE . $newTask . CLI_WARNING . ' ? (y/n)');

    if ('y' === $choice)
    {
      $argumentsVector[TasksManager::TASK_NAME] = $newTask;
      $launchCallback($tasksClassMap, $argumentsVector, $argc, $newTask);
    } else
      TasksManager::showCommands('This command does not exist. ');
  }
}
