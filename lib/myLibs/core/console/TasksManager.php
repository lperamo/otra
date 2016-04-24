<?php
declare(strict_types=1);
namespace lib\myLibs\core\console;
require_once BASE_PATH . 'lib/myLibs/core/console/Tasks.php';



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
    echo file_get_contents('LICENSE2.txt'), endColor(), PHP_EOL;
    echo PHP_EOL, brown(), $message, lightGray(), PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '- ', white(), str_pad('no argument',
      25,
      ' '), lightGray();
    echo ': ', cyan(), 'Shows the available commands.', PHP_EOL, PHP_EOL;

    $methods = get_class_methods('lib\myLibs\core\console\Tasks');

    foreach ($methods as $method)
    {
      if (false === strpos($method,
          'Desc')
      )
      {
        $methodDesc = $method . 'Desc';
        $paramsDesc = Tasks::$methodDesc();
        echo lightGray(), '- ', white(), str_pad($method,
          25,
          ' '), lightGray(), ': ', cyan(), $paramsDesc[0], PHP_EOL;
        // If we have parameters for this command, displays them
        if (isset($paramsDesc[1]))
        {
          $i = 0;
          foreach ($paramsDesc[1] as $parameter => $paramDesc)
          {
            // + parameter : (required|optional) Description
            echo lightCyan(), '   + ', str_pad($parameter,
              22,
              ' '), lightGray();
            echo ': ', lightCyan(), '(', $paramsDesc[2][$i], ') ', cyan(), $paramDesc, PHP_EOL;
            ++$i;
          }
        }
        echo PHP_EOL;
      }
    }
    echo endColor();
  }

  public static function execute(string $task, array $argv)
  {
    ini_set('display_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED);
    set_error_handler(function ($errno, $message, $file, $line, $context) {
      throw new Lionel_Exception($message, $errno, $file, $line, $context);
    });

    try
    {
      if(!file_exists(ROOTPATH . 'cache/php/ClassMap.php'))
      {
        echo yellow(), 'We cannot use the console if the class mapping file doesn\'t exist ! We launch the generation of this file ...', endColor(), PHP_EOL;
        Tasks::genClassMap();
      }

      require_once ROOTPATH . 'cache/php/ClassMap.php';
      spl_autoload_register(function(string $className) use($classMap){ require $classMap[$className]; });
      Tasks::$task($argv);
    } catch(\Exception $e)
    {
      echo (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])
        ? '{"success": "exception", "msg":' . json_encode($e->getMessage()) . '}'
        : $e->getMessage();

      return;
    }
  }
}
?>
