<?php
declare(strict_types=1);

namespace src\console\architecture\createHelloWorld;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use function otra\tools\delTree;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, OTRA_PROJECT, PROD};
use const otra\console\
{CLI_BASE, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_TABLE, CLI_WARNING, ERASE_SEQUENCE, END_COLOR, SUCCESS};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * /!\ Beware those tests will erase the bundle HelloWorld in cleaning phase !
 *
 * @runTestsInSeparateProcesses
 */
class CreateHelloWorldTaskTest extends TestCase
{
  private const
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    OTRA_LABEL_CREATED = ' created',
    OTRA_LABEL_UPDATED = ' updated',
    OTRA_LABEL_FOLDER = 'Folder ',
    OTRA_LABEL_BASE_PATH_PLUS = 'BASE_PATH + ',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;

    require CORE_PATH . 'tools/deleteTree.php';
    /** @var callable $delTree */

    if (file_exists(self::HELLO_WORLD_BUNDLE_PATH))
      delTree(self::HELLO_WORLD_BUNDLE_PATH);
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (!OTRA_PROJECT)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      delTree(self::HELLO_WORLD_BUNDLE_PATH);
      rmdir(BASE_PATH  .'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCreateHelloWorld() : void
  {
    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;

    // testing
    $this->expectOutputString(
      ERASE_SEQUENCE . CLI_BASE . 'Bundle ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld' . CLI_BASE .
      self::OTRA_LABEL_CREATED . SUCCESS .
      CLI_BASE . self::OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'HelloWorld/config' . CLI_BASE . self::OTRA_LABEL_CREATED .
      SUCCESS .
      CLI_BASE . self::OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'HelloWorld/views' . CLI_BASE . self::OTRA_LABEL_CREATED .
      SUCCESS .
      CLI_BASE . 'Basic folder architecture created for ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend' .
      SUCCESS .
      CLI_BASE . self::OTRA_LABEL_FOLDER . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend/controllers/index' .
      CLI_BASE . self::OTRA_LABEL_CREATED . SUCCESS .
      CLI_BASE . 'Action ' . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/frontend/controllers/index/HomeAction.php' .
      CLI_BASE . self::OTRA_LABEL_CREATED . SUCCESS .
      'Action filled' . SUCCESS .
      'Route configuration file ' . CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .'bundles/HelloWorld/config/Routes.php' .
      END_COLOR . self::OTRA_LABEL_CREATED . SUCCESS .
      'Security configuration folder ' . CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .
      'bundles/HelloWorld/config/security/' . END_COLOR . self::OTRA_LABEL_CREATED . SUCCESS .
      'Starter layout ' . CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/views/layout.phtml' .
      END_COLOR . self::OTRA_LABEL_CREATED . SUCCESS .
      'Starter template ' . CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT .
      'bundles/HelloWorld/frontend/views/index/home.phtml' . END_COLOR . self::OTRA_LABEL_CREATED . SUCCESS .
      'Adding stylesheets...' . PHP_EOL . ERASE_SEQUENCE . 'Stylesheets added' . SUCCESS .
      'Adding favicons...' . PHP_EOL . ERASE_SEQUENCE . 'Favicons added' . SUCCESS .
      CLI_WARNING . 'Nothing to put into ' . CLI_INFO_HIGHLIGHT . CLI_INFO . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT . 'bundles/config/Config.php' . END_COLOR .
      CLI_WARNING . ' so we\'ll delete this file if it exists.' . END_COLOR . PHP_EOL .
      CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'bundles/config/Routes.php' . CLI_BASE .
      self::OTRA_LABEL_UPDATED . SUCCESS .
      CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'cache/php/security/dev/HelloWorld.php' . CLI_BASE .
      self::OTRA_LABEL_UPDATED . SUCCESS .
      CLI_TABLE . self::OTRA_LABEL_BASE_PATH_PLUS . CLI_INFO_HIGHLIGHT . 'cache/php/security/prod/HelloWorld.php' . CLI_BASE .
      self::OTRA_LABEL_UPDATED . SUCCESS .
      CLI_BASE . 'Building the CSS assets...' . END_COLOR . PHP_EOL .
      CLI_WARNING . 'The production configuration is used for this task.' . END_COLOR . PHP_EOL .
      CLI_BASE . 'Files have been generated' . SUCCESS .
      CLI_BASE . 'CSS assets built' . SUCCESS .
      'Class mapping finished' . SUCCESS .
      'You can launch this example via the url ' . CLI_INFO_HIGHLIGHT . '/helloworld' . END_COLOR .
      '.' . PHP_EOL . 'You can launch a PHP internal web server by typing ' . CLI_INFO_HIGHLIGHT . 'otra serve' .
      END_COLOR . '.' .
      PHP_EOL
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );

    // cleaning
    if (!OTRA_PROJECT)
    {
      unlink(BUNDLES_PATH . 'config/Routes.php');
      rmdir(BUNDLES_PATH . 'config');
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
    $this->expectException(OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
  }
}
