<?php
declare(strict_types=1);

namespace src\console\deployment\genClassMap;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV,BASE_PATH,DEV,TEST_PATH};
use const otra\cache\php\init\CLASSMAP2;
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use const otra\bin\{CACHE_PHP_INIT_PATH,TASK_CLASS_MAP_PATH};

/**
 * @runTestsInSeparateProcesses
 */
class GenClassMapTaskTest extends TestCase
{
  private const
    CLASS_MAP_FILENAME = 'ClassMap.php',
    PROD_CLASS_MAP_FILENAME = 'ProdClassMap.php',
    CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . self::CLASS_MAP_FILENAME,
    PROD_CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . self::PROD_CLASS_MAP_FILENAME,
    OTRA_TASK_GEN_CLASS_MAP = 'genClassMap',
    EXAMPLES_CLASS_MAP_PATH = TEST_PATH . 'examples/genClassMap/';

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
    define(__NAMESPACE__ . '\\FIRST_CLASS_PADDING', 80);

    // testing
    $content = '';
    define(__NAMESPACE__ . '\\OTRA_MAX_FOLDERS', 296);

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
      'Development class mapping test. Here we compare ' . CLI_INFO_HIGHLIGHT . self::EXAMPLES_CLASS_MAP_PATH .
      self::CLASS_MAP_FILENAME . CLI_ERROR . ' and ' . CLI_INFO_HIGHLIGHT . self::CLASS_MAP_PATH . CLI_ERROR . '.' .
      END_COLOR
    );

    // production class map assertions
    self::assertFileExists(self::PROD_CLASS_MAP_PATH);
    self::assertFileEquals(
      self::EXAMPLES_CLASS_MAP_PATH . self::PROD_CLASS_MAP_FILENAME,
      self::PROD_CLASS_MAP_PATH,
      'Production class mapping test. Here we compare ' .CLI_INFO_HIGHLIGHT . self::EXAMPLES_CLASS_MAP_PATH .
      self::PROD_CLASS_MAP_FILENAME . CLI_ERROR . ' and ' . CLI_INFO_HIGHLIGHT . self::PROD_CLASS_MAP_PATH . CLI_ERROR .
      '.' . END_COLOR
    );
  }
}
