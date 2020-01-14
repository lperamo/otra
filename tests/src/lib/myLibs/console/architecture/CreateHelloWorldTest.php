<?php

use lib\myLibs\console\TasksManager;
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
    define('SPACE_INDENT', '  ');
    define('ERASE_SEQUENCE', "\033[1A\r\033[K");
    define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);

    if (file_exists(self::HELLO_WORLD_BUNDLE_PATH) === true)
      self::delTree(self::HELLO_WORLD_BUNDLE_PATH);
  }

  protected function tearDown(): void
  {
    self::delTree(self::HELLO_WORLD_BUNDLE_PATH);
  }

  /**
   * Deletes a tree recursively.
   *
   * @param string $dir
   *
   * @return bool
   */
  private static function delTree(string $dir) : bool
  {
    $files = array_diff(scandir($dir), ['.','..']);

    foreach ($files as &$file)
    {
      (is_dir("$dir/$file") === true)
        ? self::delTree("$dir/$file")
        : unlink("$dir/$file");
    }

    return rmdir($dir);
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
      CLI_YELLOW . 'Nothing to put into ' . CLI_LIGHT_BLUE . '/var/www/html/perso/otra/bundles/config/Config.php' . CLI_YELLOW . ' so we\'ll delete the main file if it exists.' . END_COLOR . PHP_EOL .
      CLI_GREEN . '/var/www/html/perso/otra/bundles/config/Routes.php' . ' updated.' . END_COLOR . PHP_EOL .
      CLI_LIGHT_GREEN . ' Class mapping finished.' . END_COLOR . PHP_EOL
      . PHP_EOL
      . 'The route is accessible via this url : http://dev.otra.tech/helloworld' . PHP_EOL
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      'createHelloWorld',
      ['otra.php', 'createHelloWorld']
    );
  }
  /**
   * @author Lionel Péramo
   */
  public function testCreateHelloWorld_BundleAlreadyExist() : void
  {
    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    mkdir(self::HELLO_WORLD_BUNDLE_PATH);

    // testing
    $this->expectOutputString(CLI_YELLOW . 'The bundle ' . CLI_CYAN . 'HelloWorld' . CLI_YELLOW . ' already exists.' . END_COLOR . PHP_EOL);
    $this->expectException(\lib\myLibs\OtraException::class);


    // launching
    TasksManager::execute(
      $tasksClassMap,
      'createHelloWorld',
      ['otra.php', 'createHelloWorld']
    );
  }
}
