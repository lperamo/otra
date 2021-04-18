<?php
declare(strict_types=1);

namespace src\console\deployment;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class GenClassMapTest extends TestCase
{
  private const
    CLASS_MAP_FILENAME = 'ClassMap.php',
    PROD_CLASS_MAP_FILENAME = 'ProdClassMap.php',
    CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . self::CLASS_MAP_FILENAME,
    PROD_CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . self::PROD_CLASS_MAP_FILENAME,
    OTRA_TASK_GEN_CLASS_MAP = 'genClassMap',
    EXAMPLES_CLASS_MAP_PATH = TEST_PATH . 'examples/genClassMap/',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws \otra\OtraException
   */
  public function testGenClassMapTask() : void
  {
    // context
    $_SERVER['APP_ENV'] = 'dev';
    define('FIRST_CLASS_PADDING', 80);

    // testing
    $content = '';
    define('OTRA_MAX_FOLDERS', 150);

    for ($currentFolder = 1; $currentFolder < OTRA_MAX_FOLDERS; ++$currentFolder)
    {
      $content .= "\x0d\033[K" . 'Processed directories : ' . $currentFolder . '...';
    }

    $content .= "\x0d\033[K" . 'Processed directories : ' . ($currentFolder - 1) . '.' .
      CLI_LIGHT_GREEN . ' Class mapping finished.' . END_COLOR . PHP_EOL . PHP_EOL .
      CLI_YELLOW . 'BASE_PATH = ' . BASE_PATH . PHP_EOL .
      CLI_LIGHT_BLUE . 'Class path' . CLI_GREEN . ' => ' . CLI_LIGHT_BLUE . 'Related file path' . PHP_EOL . PHP_EOL;

    require TEST_PATH . 'examples/genClassMap/ClassMap2.php';

    foreach(CLASSMAP2 as $class => &$classFile)
    {
      $content .= CLI_LIGHT_BLUE . str_pad($class, FIRST_CLASS_PADDING, '.') . CLI_GREEN . ' => ';
      $content .= (str_contains($classFile, BASE_PATH)
        // for classes inside the BASE_PATH
        ? CLI_WHITE . '[BASE_PATH]' . CLI_LIGHT_BLUE . substr($classFile, strlen(BASE_PATH))
        // for classes outside the BASE_PATH
        : CLI_LIGHT_BLUE . $classFile) .
        // and we pass to the next line !
      PHP_EOL;
    }

    // testing
    $this->expectOutputString($content . END_COLOR);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_CLASS_MAP,
      ['otra.php', self::OTRA_TASK_GEN_CLASS_MAP, 1]
    );

    // testing
    // development class map assertions
    self::assertFileExists(self::CLASS_MAP_PATH);
    self::assertFileEquals(
      self::EXAMPLES_CLASS_MAP_PATH . self::CLASS_MAP_FILENAME,
      self::CLASS_MAP_PATH,
      'Development class mapping test. Here we compare ' . self::EXAMPLES_CLASS_MAP_PATH .
      self::CLASS_MAP_FILENAME . ' and ' . self::CLASS_MAP_PATH
    );

    // production class map assertions
    self::assertFileExists(self::PROD_CLASS_MAP_PATH);
    self::assertFileEquals(
      self::EXAMPLES_CLASS_MAP_PATH . self::PROD_CLASS_MAP_FILENAME,
      self::PROD_CLASS_MAP_PATH,
      'Production class mapping test. Here we compare ' . self::EXAMPLES_CLASS_MAP_PATH .
      self::PROD_CLASS_MAP_FILENAME . ' and ' . self::PROD_CLASS_MAP_PATH
    );
  }

  /**
   * @author Lionel Péramo
   * @throws \otra\OtraException
   */
  public function testGenClassMapHelp(): void
  {
    // testing
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::OTRA_TASK_GEN_CLASS_MAP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Generates a class mapping file that will be used to replace the autoloading method.' .
      PHP_EOL . CLI_LIGHT_CYAN .
      '   + ' . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_LIGHT_CYAN . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_CYAN . 'If set to 1 => Show all the classes that will be used. Default to 0.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_CLASS_MAP]
    );
  }
}
