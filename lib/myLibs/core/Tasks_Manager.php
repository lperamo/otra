<?php

namespace lib\myLibs\core;
require_once 'Tasks.php';

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
    echo PHP_EOL, $message, PHP_EOL, PHP_EOL, 'The available commmands are : ', PHP_EOL . PHP_EOL,
      str_pad('- no argument', 25, ' '), ': Shows the available commands.', PHP_EOL, PHP_EOL;

    $methods = get_class_methods('lib\myLibs\core\Tasks');

    foreach ($methods as $method)
    {
      if (false === strpos($method, 'Desc'))
      {
        $methodDesc = $method . 'Desc';
        // str_pad is for the layout
        $methodAlign = str_pad('- ' . $method, 25, ' ');
        $paramsDesc = Tasks::$methodDesc();
        echo $methodAlign, ': ', $paramsDesc[0], PHP_EOL;

        // If we have parameters for this command, displays them
        if(isset($paramsDesc[1]))
        {
          $i = 0;
          foreach($paramsDesc[1] as $parameter => $paramDesc)
          {
            // + parameter : (required|optional) Description
            echo '   + ', str_pad($parameter, 20, ' '), ': (', $paramsDesc[2][$i], ') ',$paramDesc, PHP_EOL;
            ++$i;
          }          
        }
        echo PHP_EOL;
      }
    }
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
    require_once '../lib/myLibs/core/Universal_Loader.php';
    Tasks::$command($argv);
  }
}
?>
