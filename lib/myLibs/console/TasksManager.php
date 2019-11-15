<?
declare(strict_types=1);
namespace lib\myLibs\console;

require_once CORE_PATH . 'console/Tasks.php';

/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */
class TasksManager
{
  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands(string $message)
  {
    echo PHP_EOL, CLI_YELLOW, $message, CLI_WHITE, PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '  - ', CLI_WHITE, str_pad('no argument', 25, ' '),
    CLI_LIGHT_GRAY;
    echo ': ', CLI_CYAN, 'Shows the available commands.', PHP_EOL;

    $methods = get_class_methods('lib\myLibs\console\Tasks');
    $category = '';

    foreach ($methods as &$method)
    {
      if (false === strpos($method, 'Desc'))
      {
        $methodDesc = $method . 'Desc';
        $paramsDesc = Tasks::$methodDesc();

        if (isset($paramsDesc[TASK_CATEGORY]) === true)
        {
          if ($category !== $paramsDesc[TASK_CATEGORY])
          {
            $category = $paramsDesc[TASK_CATEGORY];
            echo CLI_BOLD_LIGHT_CYAN, PHP_EOL, '*** ', $category, ' ***', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;
          }
        } else
        {
          $category = 'Other';
          echo CLI_BOLD_LIGHT_CYAN, PHP_EOL, '*** ', $category, ' ***', PHP_EOL, PHP_EOL;
        }

        echo CLI_LIGHT_GRAY, '  - ', CLI_WHITE, str_pad($method, 25, ' '), CLI_LIGHT_GRAY, ': ', CLI_CYAN,
        $paramsDesc[TASK_DESCRIPTION],
        PHP_EOL;
      }
    }

    echo END_COLOR;
  }

  /**
   * @param string $task
   * @param array  $argv
   */
  public static function execute(string $task, array $argv)
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
          'We cannot use the console if the class mapping file doesn\'t exist ! We launch the generation of this file ...',
          END_COLOR, PHP_EOL;
        Tasks::genClassMap();

        // If the task was genClassMap...then we have nothing left to do !
        if ($task === 'genClassMap')
          exit(0);
      }

      require_once BASE_PATH . 'cache/php/ClassMap.php';
      spl_autoload_register(function(string $className) { require CLASSMAP[$className]; });
      Tasks::$task($argv);
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
