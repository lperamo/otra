<?php
declare(strict_types=1);

namespace src\console\deployment;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class GenJsRoutingTaskTest extends TestCase
{
  private const
    OTRA_TASK_INIT = 'init',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_TASK_GEN_JS_ROUTING = 'genJsRouting',
    OTRA_TASK_HELP = 'help',
    JS_ROUTING_FILENAME = 'jsRouting.js',
    MAIN_RESOURCES_PATH = BASE_PATH . 'bundles/resources/',
    MAIN_JS_ROUTING = self::MAIN_RESOURCES_PATH . self::JS_ROUTING_FILENAME,
    BACKUP_MAIN_JS_ROUTING = TEST_PATH . 'examples/' . self::JS_ROUTING_FILENAME;

  /**
   * @author Lionel Péramo
   */
  public function testGenJsRouting() : void
  {
    // context
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_INIT,
      ['otra.php', self::OTRA_TASK_INIT]
    );
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();

    // launching
    $this->expectOutputString(
      'Generating JavaScript routing...' . PHP_EOL . ERASE_SEQUENCE . 'JavaScript routing generated in ' .
      CLI_LIGHT_CYAN . self::MAIN_JS_ROUTING . END_COLOR . OTRA_SUCCESS . PHP_EOL
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_JS_ROUTING,
      ['otra.php', self::OTRA_TASK_GEN_JS_ROUTING]
    );

    // testing
    self::assertDirectoryExists(
      self::MAIN_RESOURCES_PATH,
      'Checking the existence of the folder ' . self::MAIN_RESOURCES_PATH
    );
    self::assertFileExists(
      self::MAIN_JS_ROUTING,
      'Checking the existence of the file ' . self::MAIN_JS_ROUTING
    );
    self::assertFileEquals(
      self::BACKUP_MAIN_JS_ROUTING,
      self::MAIN_JS_ROUTING,
      'Checking the contents of the file. ' . self::MAIN_JS_ROUTING . ' vs ' . self::BACKUP_MAIN_JS_ROUTING
    );

    // cleaning
    if (file_exists(MAIN_JS_ROUTING))
      unlink(self::MAIN_JS_ROUTING);

    if (file_exists(MAIN_RESOURCES_PATH))
      rmdir(MAIN_RESOURCES_PATH);
  }

  public function testSqlCleanHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_GEN_JS_ROUTING, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Generates a route mapping that can be used by JavaScript files.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_JS_ROUTING]
    );
  }
}

