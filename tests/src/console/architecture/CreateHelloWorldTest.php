<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

define('OTRA_LABEL_CREATED', ' created');
define('OTRA_LABEL_UPDATED', ' updated');
define('OTRA_LABEL_FOLDER', 'Folder ');
define('OTRA_LABEL_BASE_PATH_PLUS', 'BASE_PATH + ');
define('OTRA_TASK_CREATE_HELLO_WORLD', 'createHelloWorld');
//
//if (!defined('OTRA_SUCCESS'))
//  define('OTRA_SUCCESS', CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL);

/**
 * /!\ Beware those tests will erase the bundle HelloWorld in cleaning phase !
 *
 * @runTestsInSeparateProcesses
 */
class CreateHelloWorldTest extends TestCase
{
  const OTRA_SUCCESS = CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL;
  const HELLO_WORLD_BUNDLE_PATH = BASE_PATH . 'bundles/HelloWorld';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'prod';
    define('ERASE_SEQUENCE', "\033[1A\r\033[K");
    define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);

    require CORE_PATH . 'tools/deleteTree.php';
    /** @var callable $delTree */

    if (file_exists(self::HELLO_WORLD_BUNDLE_PATH) === true)
      $delTree(self::HELLO_WORLD_BUNDLE_PATH);
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (OTRA_PROJECT === false)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      $delTree(self::HELLO_WORLD_BUNDLE_PATH);
      rmdir(BASE_PATH  .'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateHelloWorld() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // testing
    $this->expectOutputString(
      ERASE_SEQUENCE . CLI_BASE . 'Bundle ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld' . CLI_BASE .
        OTRA_LABEL_CREATED . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_BASE . OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'HelloWorld/config' . CLI_BASE . OTRA_LABEL_CREATED .
      self::OTRA_SUCCESS .
      CLI_BASE . OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'HelloWorld/views' . CLI_BASE . OTRA_LABEL_CREATED .
      self::OTRA_SUCCESS .
      CLI_BASE . 'Basic folder architecture created for ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend' .
        self::OTRA_SUCCESS .
      CLI_BASE . OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend/controllers/index' .
        CLI_BASE . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      CLI_BASE . 'Action ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend/controllers/index/HomeAction.php' .
        CLI_BASE . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      'Action filled' . self::OTRA_SUCCESS .
      'Route configuration file ' . CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .'bundles/HelloWorld/config/Routes.php' .
        END_COLOR . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      'Security configuration folder ' . CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .
        'bundles/HelloWorld/config/security/' . END_COLOR . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      'Starter layout ' . CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/views/layout.phtml' .
        END_COLOR . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      'Starter template ' . CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .
        'bundles/HelloWorld/frontend/views/index/home.phtml' . END_COLOR . OTRA_LABEL_CREATED . self::OTRA_SUCCESS .
      'Adding stylesheets...' . PHP_EOL . ERASE_SEQUENCE . 'Stylesheets added' . self::OTRA_SUCCESS .
      'Adding favicons...' . PHP_EOL . ERASE_SEQUENCE . 'Favicons added' . self::OTRA_SUCCESS .
      CLI_WARNING . 'Nothing to put into ' . CLI_INFO_HIGHLIGHT . '/var/www/html/perso/otra/bundles/config/Config.php' .
        CLI_WARNING . ' so we\'ll delete the main file if it exists.' . END_COLOR . PHP_EOL .
      CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'bundles/config/Routes.php' . CLI_BASE .
      OTRA_LABEL_UPDATED . self::OTRA_SUCCESS .
      CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'cache/php/security/dev/HelloWorld.php' . CLI_BASE .
      OTRA_LABEL_UPDATED . self::OTRA_SUCCESS .
      CLI_TABLE . OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'cache/php/security/prod/HelloWorld.php' . CLI_BASE .
      OTRA_LABEL_UPDATED . self::OTRA_SUCCESS .
      'Class mapping finished' . self::OTRA_SUCCESS .
      PHP_EOL .
      'You can launch this example via the url ' . CLI_INFO_HIGHLIGHT . '/helloworld' . END_COLOR .
      '.' . PHP_EOL . 'You can launch a PHP internal web server by typing ' . CLI_INFO_HIGHLIGHT . 'otra serve' .
        END_COLOR . '.' .
      PHP_EOL
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', OTRA_TASK_CREATE_HELLO_WORLD]
    );

    // cleaning
    if (OTRA_PROJECT === false)
    {
      unlink(BASE_PATH . 'bundles/config/Routes.php');
      rmdir(BASE_PATH . 'bundles/config');
    }
  }
  /**
   * @author Lionel Péramo
   */
  public function testCreateHelloWorld_BundleAlreadyExist() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    mkdir(self::HELLO_WORLD_BUNDLE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_WARNING . 'The bundle ' . CLI_INFO . 'HelloWorld' . CLI_WARNING .
      ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', OTRA_TASK_CREATE_HELLO_WORLD]
    );
  }
}
