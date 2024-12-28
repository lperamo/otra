<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation
{

  use otra\OtraException;
  use PHPUnit\Framework\TestCase;
  use function otra\console\deployment\genBootstrap\separateInsideAndOutsidePhp;
  use const otra\cache\php\
  {APP_ENV, BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, PROD, TEST_PATH
  };
  use const otra\console\
  {CLI_SUCCESS, CLI_WARNING, END_COLOR
  };
  use function otra\console\deployment\genBootstrap\fixFiles;

  /**
   * It fixes issues like when AllConfig is not loaded while it should be
   *
   * @preserveGlobalState disabled
   * @runTestsInSeparateProcesses
   */
  class SeparateInsideAndOutsidePhpTest extends TestCase
  {
    protected function setUp(): void
    {
      parent::setUp();
      require CONSOLE_PATH . 'deployment/genBootstrap/processFile.php';
      define('otra\console\deployment\genBootstrap\PHP_OPEN_TAG_STRING', '<?php');
      define('otra\console\deployment\genBootstrap\PHP_END_TAG_STRING', '?>');
    }

    /**
     * Tests various use statements.
     *
     * @dataProvider fileProvider
     *
     * @param string $fileToInclude File to include
     * @param array  $expectedParts The expected extracted parts
     *
     * @return void
     */
    public function testVariousFiles(string $fileToInclude, array $expectedParts): void
    {
      $parsedClasses = [];
      ob_start();
      $parts = separateInsideAndOutsidePhp(file_get_contents($fileToInclude), $parsedClasses);
      self::assertSame('', ob_get_clean());
      self::assertSame($expectedParts, $parts);
    }

    /**
     * Provides data for testVariousUseStatements.
     *
     * @return array<string, array{0: string, 1: string, 2: array<array-key, string>}>
     */
    public static function fileProvider(): array
    {
      return [
        'First set' => [
          TEST_PATH . 'examples/deployment/separate.php',
          [
            [
              'type' => 'phpOutside',
              'content' => <<<CODE
namespace examples\\deployment\\fixFiles\\input;
class Test2
{

}
CODE
            ]
          ]
        ]
      ];
    }
  }
}
