<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation
{
  use PHPUnit\Framework\TestCase;
  use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
  use function otra\console\deployment\genBootstrap\separateInsideAndOutsidePhp;

  /**
   * It fixes issues like when AllConfig is not loaded while it should be
   *
   * @preserveGlobalState disabled
   * @runTestsInSeparateProcesses
   */
  class SeparateInsideAndOutsidePhpTest extends TestCase
  {
    private const string USED_NAMESPACE = 'otra\\console\\deployment\\genBootstrap\\';
    protected function setUp(): void
    {
      parent::setUp();
      define(self::USED_NAMESPACE . 'PHP_END_TAG_LENGTH', 2);
      define(self::USED_NAMESPACE . 'PHP_OPEN_TAG_AND_SPACE_LENGTH', 6);
      define(self::USED_NAMESPACE . 'PHP_OPEN_TAG_LENGTH', 5);
      define(self::USED_NAMESPACE . 'DECLARE_PATTERN', '@\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;@m');
      define(self::USED_NAMESPACE . 'CLASS_TOKENS', [
        T_CLASS => true,
        T_INTERFACE => true,
        T_TRAIT => true,
        T_ENUM => true
      ]);
      require CONSOLE_PATH . 'deployment/genBootstrap/separateInsideAndOutsidePhp.php';
      define('otra\console\deployment\genBootstrap\PHP_OPEN_TAG_STRING', '<?php');
      define('otra\console\deployment\genBootstrap\PHP_END_TAG_STRING', '?>');
    }

    /**
     * Tests some varied use statements. For now, we are removing namespaces. It should be different in the future.
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
              [
                  'type' => 'phpOutside',
                  'content' => <<<CODE
  class Test2
  {
  
  }
  CODE
              ],
            ],
            '',
            '',
            []
          ]
        ]
      ];
    }
  }
}
