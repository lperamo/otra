<?php
declare(strict_types=1);

namespace src\console\deployment\genClassMap;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, CLASSMAP2, CONSOLE_PATH, CORE_PATH, DEV, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genClassMap\genClassMap;
use function otra\tools\files\returnLegiblePath2;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
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
    EXAMPLES_CLASS_MAP_PATH = TEST_PATH . 'examples/genClassMap/',
    PROCESSED_DIRECTORIES = "\x0d\033[K" . 'Processed directories : ';

  /**
   * @param string $classMapFile
   *
   * @return array{string:string}
   */
  private function getClassMap(string $classMapFile) : array
  {
    $newNamespace = 'temporaryNamespace';

    // Evaluate the code with the new namespace
    eval(
      str_replace(
        [
          '<?php ',
          'namespace otra\cache\php;',
          'BASE_PATH.'
        ],
        [
          '',
          'namespace ' . $newNamespace . ';',
          '\otra\cache\php\BASE_PATH.'
        ],
        file_get_contents($classMapFile)
      )
    );

    // Retrieve the class map with the new namespace
    return constant($newNamespace . '\CLASSMAP');
  }

  private function testingClassMap(string $expectedClassMapPath, string $actualClassMapPath, string $environment)
  {
    self::assertFileExists($actualClassMapPath);
    $expectedClassMap = $this->getClassMap($expectedClassMapPath);
    $actualClassMap = $this->getClassMap($actualClassMapPath);
    sort($expectedClassMap);
    sort($actualClassMap);
    self::assertSame(
      $expectedClassMap,
      $actualClassMap,
      $environment . ' class mapping test. Here we compare ' . CLI_INFO_HIGHLIGHT .
      returnLegiblePath2($expectedClassMapPath) . CLI_ERROR . ' and ' . CLI_INFO_HIGHLIGHT .
      returnLegiblePath2($actualClassMapPath) . CLI_ERROR . '.' . END_COLOR
    );
  }

  /**
   * @medium
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testGenClassMapTask() : void
  {
    // context
    $_SERVER[APP_ENV] = DEV;
    require CORE_PATH . 'console/colors.php';
    require CORE_PATH . 'tools/files/returnLegiblePath.php';
    define(__NAMESPACE__ . '\\FIRST_CLASS_PADDING', 80);

    // testing
    $expectedContent = '';
    define(__NAMESPACE__ . '\\OTRA_MAX_FOLDERS', 453);

    for ($currentFolder = 1; $currentFolder < OTRA_MAX_FOLDERS; ++$currentFolder)
    {
      $expectedContent .= self::PROCESSED_DIRECTORIES . $currentFolder . '...';
    }

    $expectedContent .= self::PROCESSED_DIRECTORIES . ($currentFolder - 1) . '.' . PHP_EOL .
      END_COLOR . PHP_EOL .
      'Class mapping finished' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL .
      CLI_WARNING . 'BASE_PATH = ' . BASE_PATH . PHP_EOL .
      CLI_INFO . 'Class path' . CLI_INFO_HIGHLIGHT . ' => ' . CLI_INFO . 'Related file path' . PHP_EOL;

    require TEST_PATH . 'examples/genClassMap/ClassMap2.php';

    foreach (CLASSMAP2 as $class => $classFile)
    {
      $classMappingLine = CLI_INFO . str_pad($class, FIRST_CLASS_PADDING, '.') . CLI_INFO_HIGHLIGHT . ' => ';
      $classMappingLine .= (str_contains($classFile, BASE_PATH)
          // for classes inside the BASE_PATH
          ? CLI_BASE . '[BASE_PATH]' . CLI_INFO . substr($classFile, strlen(BASE_PATH))
          // for classes outside the BASE_PATH
          : CLI_INFO . $classFile);
      $expectedContent .= $classMappingLine . PHP_EOL;
    }

    $expectedContent .= 'You may have to add these classes in order to make your project work.' . PHP_EOL .
      'Maybe because you use dynamic class inclusion via require(_once)/include(_once) statements.' . PHP_EOL . PHP_EOL .
      str_pad('Class ' . CLI_WARNING . 'otra\config\AllConfig' . END_COLOR . ' ', FIRST_CLASS_PADDING,
        '.') . ' => possibly related file ' . CLI_WARNING . 'tests/config/AllConfig.php' . END_COLOR . PHP_EOL;

    ob_start();

    // launching
    require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
    genClassMap(['bin/otra.php', self::OTRA_TASK_GEN_CLASS_MAP, 1]);

    $generatedOutput = ob_get_clean();

    // Cleans and divides the generated output and expected output in lines
    $generatedLines = explode(PHP_EOL, trim($generatedOutput));
    $expectedLines = explode(PHP_EOL, trim($expectedContent));

    // Adds an extra empty line at the beginning of the expected lines
    array_unshift($expectedLines, '');

    // Sort the two lines arrays to ignore the order
    sort($generatedLines);
    sort($expectedLines);

    // Compares the two lines arrays
    self::assertSame($expectedLines, $generatedLines, 'From examples/genClassMap/ClassMap2.php');

    // testing
    $this->testingClassMap(
      self::EXAMPLES_CLASS_MAP_PATH . self::CLASS_MAP_FILENAME,
      self::CLASS_MAP_PATH,
      'Development'
    );
    $this->testingClassMap(
      self::EXAMPLES_CLASS_MAP_PATH . self::PROD_CLASS_MAP_FILENAME,
      self::PROD_CLASS_MAP_PATH,
      'Production'
    );
  }
}
