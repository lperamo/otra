<?
/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core;

use lib\myLibs\core\Database,
    config\All_Config;

class Tasks
{
  /** Executes the sql script */
  public static function sql() { exec('mysql ../sql/entire_script.sql'); }

  public static function sqlDesc() { return array('Executes the sql script'); }

  /** (sql_generate_basic) Database creation, tables creation. */
  public static function sql_gb($argv)
  {
    Database::init();
    if(isset($argv[3]))
    {
      $force = 'true' == $argv[3]; // Forces the value to be a boolean
      Database::createDatabase($argv[2], $force);
    }else
      Database::createDatabase($argv[2]);
  }

  public static function sql_gbDesc()
  {
    return array(
      'Database creation, tables creation.(sql_generate_basic)',
      array (
        'databaseName' => 'The database name !',
        'force' => 'If true, we erase the database !'
      ),
      array('required', 'optional')
    );
  }

  /** (sql_generate_fixtures) Generates fixtures. */
  public static function sql_gf($argv)
  {
    Database::init();
    if(isset($argv[3]))
    {
      $force = 'true' == $argv[3]; // Forces the value to be a boolean
      Database::createFixtures($argv[2], $force);
    }else
      Database::createFixtures($argv[2]);
  }

  public static function sql_gfDesc()
  {
    return array(
      'Generates fixtures. (sql_generate_fixtures)',
      array(
        'databaseName' => 'The database name !',
        'force' => 'If true, we erase the database !'
      ),
      array('required', 'optional')
    );
  }

  /** Clears the cache. */
  public static function cc()
  {
    array_map('unlink', glob(All_Config::$cache_path . '*.cache'));
    echo('Cache cleared.' . PHP_EOL);
  }

  public static function ccDesc() { return array('Clears the cache'); }

  public static function genAssets(){
    require 'GenAssets.php';
  }

  public static function genAssetsDesc(){
    return array('Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.');
  }
}
?>
