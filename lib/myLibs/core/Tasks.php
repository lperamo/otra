<?
/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\core;

use lib\myLibs\core\Database,
    config\All_Config;

class Tasks
{
  protected static $STRING_PAD_FOR_OPTIONAL_MASK = 40;

  /** Clears the cache. */
  public static function cc()
  {
    array_map('unlink', glob(All_Config::$cache_path . '*.cache'));
    echo('Cache cleared.' . PHP_EOL);
  }

  public static function ccDesc(): array { return array('Clears the cache'); }

  public static function crypt(array $argv)
  {
    if(isset($argv[3]))
      define(FWK_HASH, $argv[3]);
    else
      require '../config/All_Config.php';

    echo crypt($argv[2], FWK_HASH), PHP_EOL;
  }

  public static function cryptDesc() : array
  {
    return array('Crypts a password and shows it.',
      array(
        'password' => 'The password to crypt.',
        'hash' => 'The hash to use.'
      ),
      array('required', 'optional')
    );
  }

  public static function hash(array $argv)
  {
    $argv[2] = isset($argv[2]) ? $argv[2] : 7;
    $salt = '';
    $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
    for($i = 0; $i < 22; ++$i)
    {
      $salt .= $salt_chars[array_rand($salt_chars)];
    }

    echo '$2y$0' . $argv[2] . '$'. $salt, PHP_EOL;
  }

  public static function hashDesc() : array
  {
    return array('Returns a random hash.',
           array('rounds' => 'The numbers of round for the blowfish salt. Default: 7.'),
           array('optional')
    );
  }

  public static function genAssets(array $argv) { require 'GenAssets.php'; }

  public static function genAssetsDesc() : array
  {
    return array('Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.',
      array(
        'mask' => '1 => templates,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => css,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '4 => js,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '7 => all',
        'route' => 'The route for which you want to generate resources.'),
      array('optional', 'optional')
    );
  }

  /**
   * Executes the sql script
   *
   * @param array $argv
   */
  public static function sql(array $argv) { Database::executeFile('../sql/entire_script.sql', $argv[2] ?? null); }

  public static function sqlDesc() : array { return array('Executes the sql script'); }

  /**
   * @param array $argv
   */
  public static function sql_clean(array $argv) { Database::clean(isset($argv[2]) ? '1' === $argv[2]  : false); }

  public static function sql_cleanDesc() : array
  {
    return array(
      'Cleans sql and yml files in the case where there are problems that had corrupted files.',
      array('cleaningLevel' => 'Type 1 in order to also clean the file that describes the tables order.'),
      array('optional')
    );
  }

  /** (sql_generate_basic) Database creation, tables creation. */
  public static function sql_gdb(array $argv)
  {
    Database::init();

    if(isset($argv[3]))
      Database::createDatabase($argv[2], 'true' == $argv[3]); // Forces the value to be a boolean
    else
      Database::createDatabase($argv[2]);
  }

  public static function sql_gdbDesc() : array
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
  public static function sql_gf(array $argv)
  {
    Database::init();
    Database::createFixtures(
      $argv[2],
      true === isset($argv[3]) ? (int) $argv[3] : 0
    );
  }

  public static function sql_gfDesc() : array
  {
    return array(
      'Generates fixtures sql files and executes them. (sql_generate_fixtures)',
      array(
        'databaseName' => 'The database name !',
        'mask' => '1 => We erase the database' . PHP_EOL .
                  str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => We clean the fixtures sql files and we erase the database.'
      ),
      array('required', 'optional')
    );
  }

  public static function sql_is(array $argv)
  {
    Database::init();
    isset($argv[2])
      ? (isset($argv[3]) ? Database::importSchema($argv[2], $argv[3]) : Database::importSchema($argv[2]))
      : Database::importSchema();
  }

  public static function sql_isDesc() : array
  {
    return array(
      'Creates the database schema from your database. (importSchema)',
      array ('databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
             'configuration' => 'The configuration that you want to use from your configuration file.'),
      array('optional', 'optional')
    );
  }

  public static function routes()
  {
    require '../config/Routes.php';
    $alt = 0;

    foreach(\config\Routes::$_ as $route => $details)
    {
      // Routes and paths management
      $chunks = $details['chunks'];
      $altColor = ($alt % 2) ? cyan() : lightCyan();
      echo $altColor, PHP_EOL, sprintf('%-25s', $route), str_pad('Url', 10, ' '), ': ' , $chunks[0], PHP_EOL;

      if ('exception' !== $route ) {
        echo str_pad(' ', 25, ' '), str_pad('Path', 10, ' '), ': ' . $chunks[1] . '/' . $chunks[2] . '/' . $chunks[3] . 'Controller/' . $chunks[4], PHP_EOL;
      }

      $shaName = sha1('ca' . $route . All_Config::$version . 'che');

      $basePath = substr(__DIR__, 0, -15) . 'cache/';

      echo str_pad(' ', 25, ' '), 'Resources : ';
      echo (file_exists($basePath . 'php' . '/' . $route. '.php')) ? lightGreen() : lightRed(), '[PHP]', $altColor;

      // Resources management
      if(isset($details['resources']))
      {
        $resources = $details['resources'];

        if(isset($resources['_css']) || isset($resources['bundle_css']) || isset($resources['module_css']))
          echo (file_exists($basePath . 'css' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[CSS]', $altColor;

        if(isset($resources['_js']) || isset($resources['bundle_js']) || isset($resources['module_js']) || isset($resources['first_js']))
          echo (file_exists($basePath . 'js' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[JS]', $altColor;

        if(isset($resources['template']))
          echo (file_exists($basePath . 'tpl' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[TEMPLATE]', $altColor;

        echo '[', $shaName, ']', PHP_EOL, endColor();
      }else
        echo ' No other resources. ', '[', $shaName, ']', PHP_EOL, endColor();

      $alt++;
    }
  }

  public static function routesDesc() : array { return array('Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)'); }

  public static function genClassMap(){ require('GenClassMap.php'); }
  public static function genClassMapDesc() : array { return array('Generates a class mapping file that will be used to replace the autoloading method.'); }

  public static function genBootstrap(array $argv) { require('GenBootstrap.php'); }

  public static function genBootstrapDesc() : array
  {
    return array(
      'Launch the genClassMap command and generates a file that contains all the necessary php files.',
      array(
        'genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.',
        'verbose' => 'If set to 1, we print all the warnings when the task fails.',
        'route' => 'The route for which you want to generate the micro bootstrap.'),
      array('optional', 'optional', 'optional')
    );
  }
}
?>

