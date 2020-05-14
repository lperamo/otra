<?php
namespace src\console\architecture;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * /!\ Beware those tests will erase the bundle HelloWorld in cleaning phase !
 *
 * @runTestsInSeparateProcesses
 */
class CreateHelloWorldTest extends TestCase
{
  const HELLO_WORLD_BUNDLE_PATH = BASE_PATH . 'bundles/HelloWorld';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    define('ERASE_SEQUENCE', "\033[1A\r\033[K");
    define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);

    require CORE_PATH . 'tools/deleteTree.php';
    /** @var callable $delTree */

    if (file_exists(self::HELLO_WORLD_BUNDLE_PATH) === true)
      $delTree(self::HELLO_WORLD_BUNDLE_PATH);
  }

  protected function tearDown(): void
  {
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
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';

    // testing
    $this->expectOutputString(
      ERASE_SEQUENCE . CLI_GREEN . 'Bundle ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld' . CLI_GREEN . ' created.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Folder ' . CLI_LIGHT_CYAN . 'HelloWorld/config' . CLI_GREEN . ' created.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Folder ' . CLI_LIGHT_CYAN . 'HelloWorld/views' . CLI_GREEN . ' created.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Basic folder architecture created for ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld/frontend' . CLI_GREEN . '.' . END_COLOR . PHP_EOL .
      CLI_LIGHT_GREEN . 'Folder ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld/frontend/controllers/index' . CLI_LIGHT_GREEN . ' created.' . END_COLOR . PHP_EOL .
      CLI_LIGHT_GREEN . 'Action ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld/frontend/controllers/index/HomeAction.php' . CLI_LIGHT_GREEN . ' created.' . END_COLOR . PHP_EOL .
      CLI_GREEN . 'Action filled.' . PHP_EOL .
      'Route configuration file ' . CLI_LIGHT_CYAN . '/var/www/html/perso/otra/bundles/HelloWorld/config/Routes.php' . CLI_GREEN . ' created.' . PHP_EOL .
      CLI_GREEN . 'Starter layout created in ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld/views/layout.phtml' . CLI_GREEN . '.' . PHP_EOL .
      'Starter template created in ' . CLI_LIGHT_CYAN . 'bundles/HelloWorld/frontend/views/index/home.phtml' . CLI_GREEN . '.' . PHP_EOL .
      'Adding favicons...' . PHP_EOL .
      CLI_YELLOW . 'Nothing to put into ' . CLI_LIGHT_BLUE . '/var/www/html/perso/otra/bundles/config/Config.php' . CLI_YELLOW . ' so we\'ll delete the main file if it exists.' . END_COLOR . PHP_EOL .
      CLI_GREEN . '/var/www/html/perso/otra/bundles/config/Routes.php' . ' updated.' . END_COLOR . PHP_EOL .
      CLI_LIGHT_GREEN . ' Class mapping finished.' . END_COLOR . PHP_EOL
      . PHP_EOL
      . CLI_GREEN . 'You can launch this example via the url ' . CLI_LIGHT_CYAN . '/helloworld' . CLI_GREEN .
      '.' . PHP_EOL . 'You can launch a PHP internal web server by typing ' . CLI_LIGHT_CYAN . 'otra serve' . CLI_GREEN . '.' .
      END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      'createHelloWorld',
      ['otra.php', 'createHelloWorld']
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
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(self::HELLO_WORLD_BUNDLE_PATH, 0777, true);

    // testing
    $this->expectOutputString(CLI_YELLOW . 'The bundle ' . CLI_CYAN . 'HelloWorld' . CLI_YELLOW . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\otra\OtraException::class);

    // launching
    TasksManager::execute(
      $tasksClassMap,
      'createHelloWorld',
      ['otra.php', 'createHelloWorld']
    );
  }
}
