<?
/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\console;

class Tasks
{
  protected static $STRING_PAD_FOR_OPTIONAL_MASK = 40;

  /**
   * @param array|null $argv
   */
  public static function cc(?array $argv = null)
  {
    require CORE_PATH . 'console/ClearCache.php';
  }

  /**
   * @return array
   */
  public static function ccDesc(): array
  {
    return [
      'Clears the cache',
      [
        'route name' => 'If you want to clear cache for only one route.'
      ],
      ['optional']
    ];
  }

  /**
   * @param array $argv
   */
  public static function createBundle(array $argv)
  {
    require CORE_PATH . 'console/CreateBundle.php';
  }

  /**
   * @return array
   */
  public static function createBundleDesc(): array {
    return [
      'Creates a bundle.' . brownText('[PARTIALLY IMPLEMENTED]'),
      [
        'bundle name' => 'The name of the bundle !',
        'mask' => 'In addition to the module, it will create a folder for :' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '0 => nothing' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '1 => models' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => resources' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '4 => views'
      ],
      [
        'optional',
        'optional'
      ]
    ];
  }

  /**
   * @param array $argv
   */
  public static function createModel(array $argv)
  {
    require CORE_PATH . 'console/CreateModel.php';
  }

  /**
   * @return array
   */
  public static function createModelDesc() : array {
    return [
      'Creates a model.',
      [
        'bundle' => 'The bundle in which the model',
        'how' => '1 => Creates from nothing' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => One model from '. brown() . 'schema.yml' . cyan(). PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '3 => All models from ' . brown() .'schema.yml' . cyan()
      ],
      ['optional', 'optional']
    ];
  }

  /**
   * @param array $argv
   */
  public static function crypt(array $argv)
  {
    if (true === isset($argv[3]))
      define(FWK_HASH, $argv[3]);
    else
      require BASE_PATH . 'config/AllConfig.php';

    echo crypt($argv[2], FWK_HASH), PHP_EOL;
  }

  /**
   * @return array
   */
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

  /**
   * @param array $argv
   */
  public static function deploy(array $argv) { require CORE_PATH . 'console/Deploy.php'; }

  public static function deployDesc() : array {
    return [
      'Deploy the site. ' . brownText('[WIP - Do not use yet !]'),
      [
        'mode' => '0 => Nothing to do (default)' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '1 => Generates php production files.' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => Same as 1 + resource production files.' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '3 => Same as 2 + class mapping',
        'verbose' => 'If set to 1 => we print all the warnings during the production php files generation'
      ],
      ['optional', 'optional']
    ];
  }

  /**
   * @param array $argv
   */
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

  /**
   * @return array
   */
  public static function hashDesc() : array
  {
    return [
      'Returns a random hash.',
      ['rounds' => 'The numbers of round for the blowfish salt. Default: 7.'],
      ['optional']
    ];
  }

  /**
   * @param array $argv
   */
  public static function genAssets(array $argv) { require CORE_PATH . 'console/GenAssets.php'; }

  /**
   * @return array
   */
  public static function genAssetsDesc() : array
  {
    return [
      'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.',
      [
        'mask' => '1 => templates' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '2 => css' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '4 => js' . PHP_EOL .
          str_repeat(' ', self::$STRING_PAD_FOR_OPTIONAL_MASK) . '7 => all',
        'route' => 'The route for which you want to generate resources.'
      ],
      ['optional', 'optional']
    ];
  }

  /**
   * @param array $argv
   */
  public static function genBootstrap(array $argv) { require CORE_PATH . 'console/GenBootstrap.php'; }

  /**
   * @return array
   */
  public static function genBootstrapDesc() : array
  {
    return [
      'Launch the genClassMap command and generates a file that contains all the necessary php files.',
      [
        'genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.',
        'verbose' => 'If set to 1, we print all the main warnings when the task fails. Put 2 to get every warning.',
        'route' => 'The route for which you want to generate the micro bootstrap.'
      ],
      ['optional', 'optional', 'optional']
    ];
  }

  /**
   * Generates the class mapping. If the only parameters in argv is set to 1 => show all the...cf. description
   *
   * @param array|null|null $argv
   */
  public static function genClassMap(?array $argv = null) { require CORE_PATH . 'console/GenClassMap.php'; }
  public static function genClassMapDesc() : array
  {
    return [
      'Generates a class mapping file that will be used to replace the autoloading method.',
      ['verbose' => 'If set to 1 => Show all the classes that will be used. Default to 0.'],
      ['optional']
    ];
  }

  /**
   * Show the help for the specified command.
   *
   * @param array $argv
   */
  public static function help(array $argv) { require CORE_PATH . 'console/Help.php'; }

  /**
   * @return array
   */
  public static function helpDesc() : array
  {
    return [
      'Shows the extended help for the specified command.',
      ['command' => 'The command which you need help for.'],
      ['required']
    ];
  }

  public static function upConf() { require CORE_PATH . 'console/UpdateConf.php'; }

  /**
   * @return array
   */
  public static function upConfDesc() : array { return ['Updates the files related to bundles and routes.']; }

  /**
   * Executes the sql script
   *
   * @param array $argv
   */
  public static function sql(array $argv)
  {
    Database::executeFile($argv[2], $argv[3] ?? null);
  }

  public static function sqlDesc() : array
  {
    return [
      'Executes the sql script',
      [
        'file' => 'File that will be executed',
        'database' => 'Database to use for this script'
      ],
      ['required', 'optional']
    ];
  }

  /**
   * @param array $argv
   */
  public static function sql_clean(array $argv) { Database::clean(isset($argv[2]) ? '1' === $argv[2]  : false); }

  /**
   * @return array
   */
  public static function sql_cleanDesc() : array
  {
    return [
      'Removes sql and yml files in the case where there are problems that had corrupted files.',
      ['cleaningLevel' => 'Type 1 in order to also remove the file that describes the tables order.'],
      ['optional']
    ];
  }

  /**
   * (sql_generate_basic) Database creation, tables creation.
   *
   * @param array $argv
   *
   * @throws \lib\myLibs\LionelException
   */
  public static function sql_gdb(array $argv)
  {
      Database::createDatabase(
        $argv[2],
        true === isset($argv[3])
          ? 'true' == $argv[3] // Forces the value to be a boolean
          : false
      );
  }

  /**
   * @return array
   */
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

  /** (sql_generate_fixtures) Generates fixtures.
   *
   * @param array $argv
   *
   * @throws \lib\myLibs\LionelException
   */
  public static function sql_gf(array $argv)
  {
    Database::createFixtures(
      $argv[2],
      true === isset($argv[3]) ? (int) $argv[3] : 0
    );
  }

  /**
   * @return array
   */
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

  /**
   * @param array $argv
   *
   * @throws \lib\myLibs\LionelException
   */
  public static function sql_is(array $argv)
  {
    isset($argv[2])
      ? (isset($argv[3]) ? Database::importSchema($argv[2], $argv[3]) : Database::importSchema($argv[2]))
      : Database::importSchema();
  }

  /**
   * @return array
   */
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

  /**
   * @param array $argv
   *
   * @throws \lib\myLibs\LionelException
   */
  public static function sql_if(array $argv)
  {
    isset($argv[2])
      ? (isset($argv[3]) ? Database::importFixtures($argv[2], $argv[3]) : Database::importFixtures($argv[2]))
      : Database::importFixtures();
  }

  /**
   * @return array
   */
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

  /**
   * @param array $argv
   */
  public static function routes(array $argv) { require CORE_PATH . 'console/Routes.php'; }

  /**
   * @return array
   */
  public static function routesDesc() : array
  {
    return [
      'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)',
      ['route' => 'The name of the route that we want information from, if we wish only one route description.'],
      ['optional']
    ];
  }

  public static function version() {
    echo file_get_contents(CORE_PATH . 'console/LICENSE2.txt'), endColor(), PHP_EOL, brownText('Version 1.0 ALPHA.');
  }

  /**
   * @return array
   */
  public static function versionDesc() : array { return ['Shows the framework version.']; }
}
?>

