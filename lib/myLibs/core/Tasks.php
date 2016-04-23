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
    echo 'Cache cleared.', PHP_EOL;
  }

  public static function ccDesc(): array { return array('Clears the cache'); }

  public static function crypt(array $argv)
  {
    if(isset($argv[3]))
      define(FWK_HASH, $argv[3]);
    else
      require BASE_PATH . 'config/All_Config.php';

    echo crypt($argv[2], FWK_HASH), PHP_EOL;
  }

  public static function cryptDesc() : array
  {
    return [
      'Crypts a password and shows it.',
      [
        'password' => 'The password to crypt.',
        'hash' => 'The hash to use.'
      ],
      ['required', 'optional']
    ];
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

    echo '$2y$0', $argv[2], '$', $salt, PHP_EOL;
  }

  public static function hashDesc() : array
  {
    return [
      'Returns a random hash.',
      ['rounds' => 'The numbers of round for the blowfish salt. Default: 7.'],
      ['optional']
    ];
  }

  public static function genAssets(array $argv) { require 'GenAssets.php'; }

  public static function genAssetsDesc() : array
  {
    return [
      'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.',
      [
        'mask' => '1 => templates,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => css,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '4 => js,' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '7 => all',
        'route' => 'The route for which you want to generate resources.'
      ],
      ['optional', 'optional']
    ];
  }

  /**
   * Executes the sql script
   *
   * @param array $argv
   */
  public static function sql(array $argv) { Database::executeFile('../sql/entire_script.sql', $argv[2] ?? null); }

  public static function sqlDesc() : array { return ['Executes the sql script']; }

  /**
   * @param array $argv
   */
  public static function sql_clean(array $argv) { Database::clean(isset($argv[2]) ? '1' === $argv[2]  : false); }

  public static function sql_cleanDesc() : array
  {
    return [
      'Cleans sql and yml files in the case where there are problems that had corrupted files.',
      ['cleaningLevel' => 'Type 1 in order to also clean the file that describes the tables order.'],
      ['optional']
    ];
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
    return [
      'Database creation, tables creation.(sql_generate_basic)',
      [
        'databaseName' => 'The database name !',
        'force' => 'If true, we erase the database !'
      ],
      ['required', 'optional']
    ];
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
    return [
      'Generates fixtures sql files and executes them. (sql_generate_fixtures)',
      [
        'databaseName' => 'The database name !',
        'mask' => '1 => We erase the database' . PHP_EOL .
                  str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => We clean the fixtures sql files and we erase the database.'
      ],
      ['required', 'optional']
    ];
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
    return [
      'Creates the database schema from your database. (importSchema)',
      [
        'databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
        'configuration' => 'The configuration that you want to use from your configuration file.'
      ],
      ['optional', 'optional']
    ];
  }

  public static function sql_if(array $argv)
  {
    Database::init();
    isset($argv[2])
      ? (isset($argv[3]) ? Database::importFixtures($argv[2], $argv[3]) : Database::importFixtures($argv[2]))
      : Database::importFixtures();
  }

  public static function sql_ifDesc() : array
  {
    return [
      'Import the fixtures from database into ' . brown() . 'config/data/yml/fixtures' . cyan() . '.',
      [
        'databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
        'configuration' => 'The configuration that you want to use from your configuration file.'
      ],
      ['optional', 'optional']
    ];
  }

  public static function routes(array $argv) { require 'Routes.php'; }

  public static function routesDesc() : array
  {
    return [
      'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)',
      ['route' => 'The name of the route that we want information from, if we wish only one route description.'],
      ['optional']
    ];
  }

  public static function genClassMap(){ require 'GenClassMap.php'; }
  public static function genClassMapDesc() : array { return ['Generates a class mapping file that will be used to replace the autoloading method.']; }

  public static function genBootstrap(array $argv) { require 'GenBootstrap.php'; }

  public static function genBootstrapDesc() : array
  {
    return [
      'Launch the genClassMap command and generates a file that contains all the necessary php files.',
      [
        'genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.',
        'verbose' => 'If set to 1, we print all the warnings when the task fails.',
        'route' => 'The route for which you want to generate the micro bootstrap.'
      ],
      ['optional', 'optional', 'optional']
    ];
  }
}
?>

