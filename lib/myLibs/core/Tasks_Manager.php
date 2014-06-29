<?php

namespace lib\myLibs\core;
require_once ROOTPATH . 'lib/myLibs/core/Tasks.php';

/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */
class Tasks_Manager
{
  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands($message)
  {
    echo PHP_EOL , lightRed() , $message, lightGray(), PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '- ', white(), str_pad('no argument', 25, ' '), lightGray();
    echo ': ', cyan(), 'Shows the available commands.', PHP_EOL, PHP_EOL;

    $methods = get_class_methods('lib\myLibs\core\Tasks');

    foreach ($methods as $method)
    {
      if (false === strpos($method, 'Desc'))
      {
        $methodDesc = $method . 'Desc';
        $paramsDesc = Tasks::$methodDesc();
        echo lightGray(), '- ', white(), str_pad($method, 25, ' '), lightGray(), ': ', cyan(), $paramsDesc[0], PHP_EOL;
        // If we have parameters for this command, displays them
        if(isset($paramsDesc[1]))
        {
          $i = 0;
          foreach($paramsDesc[1] as $parameter => $paramDesc)
          {
            // + parameter : (required|optional) Description
            echo lightBlue(), '   + ', str_pad($parameter, 22, ' '), lightGray();
            echo ': ', cyan(), '(', $paramsDesc[2][$i], ') ', $paramDesc, PHP_EOL;
            ++$i;
          }
        }
        echo PHP_EOL;
      }
    }
    echo endColor();
  }

  /**
   * Executes a function depending on the type of the command
   *
   * @param string $command The command name
   * @param array  $argv    The arguments to pass
   */
  public static function execute($command, array $argv)
  {
    // We have to initialize the database before making any database operations...
//    if (false !== strpos($command, 'sql'))
//      self::initDb();
    define('DS', DIRECTORY_SEPARATOR);
    define('AVT', '..' . DS);

    if(!file_exists(ROOTPATH . 'lib/myLibs/core/ClassMap.php'))
    {
      echo yellow(), 'We cannot use the console if the class mapping file doesn\'t exist ! We launch the generation of this file ...', endColor(), PHP_EOL;
      Tasks::genClassMap();
    }

    require_once ROOTPATH . 'lib/myLibs/core/ClassMap.php';
    spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });
    Tasks::$command($argv);
  }
}
?>
