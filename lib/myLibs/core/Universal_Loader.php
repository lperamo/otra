<?php
/** An universal autoloader !
 *
 * @author Lionel Péramo */

final class Universal_Autoloader
{
  /**
   * Load a kernel or lib class
   *
   * @param strings $className Name of the class
   *
   * @return bool True class is loaded
   */
  public static function autoload($className)
  {
    $namespaces = (isset($_SERVER['DOCUMENT_ROOT']))
      ? array(
        $_SERVER['DOCUMENT_ROOT'] . '..' . DS => 'before',
        __DIR__ . DS . '..' . DS . '..' . DS . 'sf2_yaml' => 'beforeWN'
      )
      : array(
        $_SERVER['PWD'] . DS . '..' . DS => 'before',
        $_SERVER['PWD'] . DS . '..' . DS . 'lib' . DS .'sf2_yaml' => 'beforeWN',
      );

    // We get the last folder name that must be the file name
    $temp = strrpos($className, '_');
    $class = substr($className, $temp + (int) (false !== $temp));

    // Test in $folder/classname.class.php, ex: Core_File to core/file/file.class.php
    $file = $className . '.php';

    // try all the specified namespaces in order to find the file
    reset($namespaces);
    foreach ($namespaces as $namespace => $position)
    {
      if('WN' == substr($position, -2))
      {
        $class = str_replace('\\', DS, $class);
        $pos = strrpos($class, DS);
        $file = (false !== $pos) ? substr($class, $pos) .'.php' : $class . '.php';
      }
      $tempFile = $namespace . str_replace('\\', DS, $file);

      if (file_exists($tempFile))
      {
        require_once $tempFile;
        return true;
      }
    }

    return false;
  }
}
spl_autoload_register(array('Universal_Autoloader', 'autoload'));
?>
