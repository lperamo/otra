<?php
declare(strict_types=1);

namespace src\console\deployment\genJsRouting;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BUNDLES_PATH, TEST_PATH};
use const otra\console\
{CLI_ERROR, CLI_INFO_HIGHLIGHT, ERASE_SEQUENCE, END_COLOR, SUCCESS};
use const otra\console\deployment\genJsRouting\{MAIN_JS_ROUTING, MAIN_RESOURCES_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class GenJsRoutingTaskTest extends TestCase
{
  private const
    OTRA_TASK_INIT = 'init',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_TASK_GEN_JS_ROUTING = 'genJsRouting',
    JS_ROUTING_FILENAME = 'jsRouting.js',
    MAIN_RESOURCES_PATH = BUNDLES_PATH . 'resources/',
    MAIN_JS_ROUTING = self::MAIN_RESOURCES_PATH . self::JS_ROUTING_FILENAME,
    BACKUP_MAIN_JS_ROUTING = TEST_PATH . 'examples/' . self::JS_ROUTING_FILENAME,
    OTRA_BINARY = 'otra.php';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testGenJsRouting() : void
  {
    // context
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_INIT,
      [self::OTRA_BINARY, self::OTRA_TASK_INIT]
    );
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();

    // launching
    $this->expectOutputString(
      'Generating JavaScript routing...' . PHP_EOL . ERASE_SEQUENCE . 'JavaScript routing generated in ' .
      CLI_INFO_HIGHLIGHT . self::MAIN_JS_ROUTING . END_COLOR . SUCCESS
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_JS_ROUTING,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_JS_ROUTING]
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
      'Checking the contents of the file. ' . CLI_INFO_HIGHLIGHT . self::MAIN_JS_ROUTING . CLI_ERROR . ' vs ' .
      CLI_INFO_HIGHLIGHT . self::BACKUP_MAIN_JS_ROUTING . CLI_ERROR
    );

    // cleaning
    if (file_exists(MAIN_JS_ROUTING))
      unlink(self::MAIN_JS_ROUTING);

    if (file_exists(MAIN_RESOURCES_PATH))
      rmdir(MAIN_RESOURCES_PATH);
  }
}
