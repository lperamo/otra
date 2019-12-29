<?php
declare(strict_types=1);
namespace lib\myLibs\console;

/** @author Lionel Péramo */
abstract class TasksManager
{
  const STRING_PAD_FOR_OPTION_FORMATTING = 40;
  const TASK_CLASS_MAP_TASK_PATH = 0;
  const TASK_CLASS_MAP_TASK_STATUS = 1;
  const TASK_DESCRIPTION = 0;
  const TASK_PARAMETERS = 1;
  const TASK_STATUS = 2;
  const TASK_CATEGORY = 3;
  const TASK_PATH = 4;

  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands(string $message)
  {
    define('HELP_BETWEEN_TASK_AND_COLON', 28);
    echo PHP_EOL, CLI_YELLOW, $message, CLI_WHITE, PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '  - ', CLI_WHITE, str_pad('no argument', HELP_BETWEEN_TASK_AND_COLON, ' '),
    CLI_LIGHT_GRAY;
    echo ': ', CLI_CYAN, 'Shows the available commands.', PHP_EOL;

    $methods = require BASE_PATH . 'cache/php/tasksHelp.php';
    $category = '';

    foreach ($methods as $method => &$paramsDesc)
    {
      if (isset($paramsDesc[self::TASK_CATEGORY]) === true)
      {
        if ($category !== $paramsDesc[self::TASK_CATEGORY])
        {
          $category = $paramsDesc[self::TASK_CATEGORY];
          echo CLI_BOLD_LIGHT_CYAN, PHP_EOL, '*** ', $category, ' ***', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;
        }
      } else
      {
        $category = 'Other';
        echo CLI_BOLD_LIGHT_CYAN, PHP_EOL, '*** ', $category, ' ***', PHP_EOL, PHP_EOL;
      }

      echo CLI_LIGHT_GRAY, '  - ', CLI_WHITE, str_pad($method, HELP_BETWEEN_TASK_AND_COLON, ' '), CLI_LIGHT_GRAY, ': ', CLI_CYAN,
      $paramsDesc[self::TASK_DESCRIPTION],
      PHP_EOL;
    }

    echo END_COLOR;
  }

  /**
   * @param array  $tasksClassMap
   * @param string $task
   * @param array  $argv
   */
  public static function execute(array $tasksClassMap, string $task, array $argv)
  {
    ini_set('display_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED);
    require CORE_PATH . 'OtraException.php';

    set_error_handler(function ($errno, $message, $file, $line, $context) {
      throw new \lib\myLibs\OtraException($message, $errno, $file, $line, $context);
    });

    try
    {
      if (false === file_exists(BASE_PATH . 'cache/php/ClassMap.php'))
      {
        echo CLI_YELLOW,
          'We cannot use the console if the class mapping files do not exist ! We launch the generation of those files ...',
          END_COLOR, PHP_EOL;
        require $tasksClassMap['genClassMap'][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $task . 'Task.php';

        // If the task was genClassMap...then we have nothing left to do !
        if ($task === 'genClassMap')
          exit(0);
      }

      require_once BASE_PATH . 'cache/php/ClassMap.php';
      spl_autoload_register(function(string $className) { require CLASSMAP[$className]; });
      require $tasksClassMap[$task][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/' . $task . 'Task.php';
    } catch(\Exception $e)
    {
      if (true === isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])
        echo '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}';

      // no need for $e->getMessage if it's not the case ? i believe in this case $e->getMessage() is already used

      return;
    }
  }
}
?>
