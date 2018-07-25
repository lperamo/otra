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
    echo PHP_EOL, brown(), $message, lightGray(), PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '- ', white(), str_pad('no argument', 25, ' '), lightGray();
    echo ': ', cyan(), 'Shows the available commands.', PHP_EOL, PHP_EOL;

    $methods = get_class_methods('lib\myLibs\console\Tasks');

    foreach ($methods as &$method)
    {
      if (false === strpos($method, 'Desc'))
      {
        $methodDesc = $method . 'Desc';
        $paramsDesc = Tasks::$methodDesc();
        echo lightGray(), '- ', white(), str_pad($method, 25, ' '), lightGray(), ': ', cyan(), $paramsDesc[0], PHP_EOL;
      }
    }
    echo endColor();
  }

  public static function execute(string $task, array $argv)
  {
    ini_set('display_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED);
    require CORE_PATH . 'Lionel_Exception.php';

    set_error_handler(function ($errno, $message, $file, $line, $context) {
      throw new \lib\myLibs\Lionel_Exception($message, $errno, $file, $line, $context);
    });

    try
    {
      if (false === file_exists(BASE_PATH . 'cache/php/ClassMap.php'))
      {
        echo yellowText('We cannot use the console if the class mapping file doesn\'t exist ! We launch the generation of this file ...'), PHP_EOL;
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
