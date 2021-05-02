<?php
declare(strict_types=1);

namespace src\console\deployment;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV,BASE_PATH,DEV,TEST_PATH};
use const otra\cache\php\init\CLASSMAP2;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use const otra\bin\{CACHE_PHP_INIT_PATH,TASK_CLASS_MAP_PATH};

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

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testGenClassMapTask() : void
  {
    // context
    $_SERVER[APP_ENV] = DEV;
    define('src\console\deployment\FIRST_CLASS_PADDING', 80);

    // testing
    $content = '';
    define('src\console\deployment\OTRA_MAX_FOLDERS', 150);

    for ($currentFolder = 1; $currentFolder < OTRA_MAX_FOLDERS; ++$currentFolder)
    {
      $content .= "\x0d\033[K" . 'Processed directories : ' . $currentFolder . '...';
    }

    $content .= "\x0d\033[K" . 'Processed directories : ' . ($currentFolder - 1) . '.' . PHP_EOL .
      'Class mapping finished' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_WARNING . 'BASE_PATH = ' . BASE_PATH . PHP_EOL .
      CLI_INFO . 'Class path' . CLI_INFO_HIGHLIGHT . ' => ' . CLI_INFO . 'Related file path' . PHP_EOL . PHP_EOL;

    require TEST_PATH . 'examples/genClassMap/ClassMap2.php';

    foreach(CLASSMAP2 as $class => $classFile)
    {
      $content .= CLI_INFO . str_pad($class, FIRST_CLASS_PADDING, '.') . CLI_INFO_HIGHLIGHT . ' => ';
      $content .= (str_contains($classFile, BASE_PATH)
        // for classes inside the BASE_PATH
        ? CLI_BASE . '[BASE_PATH]' . CLI_INFO . substr($classFile, strlen(BASE_PATH))
        // for classes outside the BASE_PATH
        : CLI_INFO . $classFile) .
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
   * @throws OtraException
   */
  public function testGenClassMapHelp(): void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_GEN_CLASS_MAP, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates a class mapping file that will be used to replace the autoloading method.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('verbose', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to 1 => Show all the classes that will be used. Default to 0.' . PHP_EOL . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_CLASS_MAP]
    );
  }
}
