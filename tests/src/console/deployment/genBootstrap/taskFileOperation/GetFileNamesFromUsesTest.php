<?php
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap
{
  const VERBOSE = 2;
}

namespace src\console\deployment\genBootstrap\taskFileOperation
{
  use PHPUnit\Framework\TestCase;
  use const otra\cache\php\CONSOLE_PATH;
  use function otra\console\deployment\genBootstrap\{getFileNamesFromUses};

  /**
   * It fixes issues like when AllConfig is not loaded while it should be
   * @preserveGlobalState disabled
   * @runTestsInSeparateProcesses
   */
  class GetFileNamesFromUsesTest extends TestCase
  {
    private const int LEVEL = 1;
    private const string
      EOL_CLASS1 = PHP_EOL . 'class Class1{}',
      EOL_CLASS2 = PHP_EOL . 'class Class2{}',
      EOL_CLASS3 = PHP_EOL . 'class Class3{}',
      USE_SOME_NAMESPACE = 'use Some\\Namespace';

    protected function setUp(): void
    {
      parent::setUp();
      require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    }

    /**
     * Tests various use statements.
     *
     * @dataProvider useStatementsProvider
     *
     * @param string                   $contentToAdd          The use statement to test.
     * @param string                   $expectedContent       The expected content after replacement
     * @param string                   $expectedOutput        The expected output.
     * @param array<array-key, string> $expectedFilesToConcat The expected files to concatenate.
     *
     * @return void
     */
    public function testVariousUseStatements(
      string $contentToAdd,
      string $expectedContent,
      string $expectedOutput,
      array $expectedFilesToConcat
    ) : void
    {
      $filesToConcat = $parsedConstants = $parsedFiles = $parsedClasses = [];

      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants,
        $parsedClasses
      );

      $this->expectOutputString($expectedOutput);
      static::assertSame($expectedFilesToConcat, $filesToConcat, 'Testing the files array to include');
      static::assertSame($expectedContent, $contentToAdd, 'Testing replaced content');
      static::assertSame([], $parsedConstants, 'Testing parsed constants');
      static::assertSame([], $parsedFiles, 'Testing parsed files');
    }

    /**
     * Provides data for testVariousUseStatements.
     *
     * @return array<string, array{0: string, 1: string, 2: array<array-key, string>}>
     */
    public static function useStatementsProvider() : array
    {
      return [
        'Simple use' =>
        [
          self::USE_SOME_NAMESPACE . '\\Class1;' . self::EOL_CLASS1,
          self::EOL_CLASS1,
          '',
          []
        ],
        'Multiple classes' =>
        [
          self::USE_SOME_NAMESPACE . '\\{Stripe, Class2, Class3};' . PHP_EOL . 'class Stripe{}' . self::EOL_CLASS2 .
          self::EOL_CLASS3,
          PHP_EOL . 'class Stripe{}' . self::EOL_CLASS2 . self::EOL_CLASS3,
          '',
          []
        ],
        'With carriage return' =>
        [
          self::USE_SOME_NAMESPACE . PHP_EOL . '{Class1,Class2};' . self::EOL_CLASS1 . self::EOL_CLASS2,
          self::EOL_CLASS1 . self::EOL_CLASS2,
          '',
          [],
        ]
      ];
    }

    public function testNoReplacementInVariableNames(): void
    {
      // Context
      $contentToAdd = <<<EOD
    \$otra\cache\php\BASE_PATH = '/some/path/';
    \$config = require otra\cache\php\BASE_PATH . 'config/' . \$_SERVER[otra\cache\php\APP_ENV] . '/AllConfig.php';
    use SomeNamespace\SomeClass;
    EOD;

      $filesToConcat = $parsedConstants = $parsedFiles = $parsedClasses = [];

      // Run
      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants,
        $parsedClasses
      );

      // Test
      $expectedContent = <<<EOD
    \$otra\cache\php\BASE_PATH = '/some/path/';
    \$config = require otra\cache\php\BASE_PATH . 'config/' . \$_SERVER[otra\cache\php\APP_ENV] . '/AllConfig.php';
    
    EOD;

      static::assertEquals($expectedContent, $contentToAdd);
      static::assertEmpty($filesToConcat);
      $this->expectOutputString('');
    }
  }
}
