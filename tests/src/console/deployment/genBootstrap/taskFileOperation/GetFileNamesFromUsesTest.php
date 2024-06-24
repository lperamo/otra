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
  use const otra\console\CLI_WARNING;
  use const otra\console\END_COLOR;

  /**
   * It fixes issues like when AllConfig is not loaded while it should be
   * @preserveGlobalState disabled
   * @runTestsInSeparateProcesses
   */
  class GetFileNamesFromUsesTest extends TestCase
  {
    private const LEVEL = 1;

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
     * @param string                   $useStatement          The use statement to test.
     * @param string                   $expectedOutput        The expected output.
     * @param array<array-key, string> $expectedFilesToConcat The expected files to concatenate.
     *
     * @return void
     */
    public function testVariousUseStatements(string $useStatement, string $expectedOutput, array $expectedFilesToConcat) : void
    {
      $contentToAdd = PHP_EOL . $useStatement;
      $filesToConcat = $parsedConstants = $parsedFiles = [];

      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants
      );

      $this->expectOutputString($expectedOutput);
      static::assertSame($expectedFilesToConcat, $filesToConcat);
      static::assertSame([], $parsedConstants);
      static::assertSame([], $parsedFiles);
    }

    /**
     * Provides data for testVariousUseStatements.
     *
     * @return array<string, array{0: string, 1: string, 2: array<array-key, string>}>
     */
    public static function useStatementsProvider() : array
    {
      return [
        'Simple use' => [
          'use Some\Namespace\Class;',
          CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\Namespace\Class' . END_COLOR . PHP_EOL,
          []
        ],
        'Multiple classes' => [
          'use Some\Namespace\{Class1, Class2, Class3};',
            CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\Namespace\Class1' . END_COLOR . PHP_EOL .
            CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\Namespace\Class2' . END_COLOR . PHP_EOL .
            CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\Namespace\Class3' . END_COLOR . PHP_EOL,
          []
        ],
        'With carriage return' => [
          'use Some\\Namespace' . "\n" . '{Class1,Class2};',
          CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\\Namespace\\Class1' . END_COLOR . PHP_EOL .
          CLI_WARNING . 'EXTERNAL LIBRARY CLASS : Some\\Namespace\\Class2' . END_COLOR . PHP_EOL,
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

      $filesToConcat = $parsedConstants = $parsedFiles = [];

      // Run
      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants
      );

      // Test
      $expectedContent = <<<EOD
    \$otra\cache\php\BASE_PATH = '/some/path/';
    \$config = require otra\cache\php\BASE_PATH . 'config/' . \$_SERVER[otra\cache\php\APP_ENV] . '/AllConfig.php';
    
    EOD;

      static::assertEquals($expectedContent, $contentToAdd);
      static::assertEmpty($filesToConcat);
      $this->expectOutputString(CLI_WARNING . 'EXTERNAL LIBRARY CLASS : SomeNamespace\\SomeClass' . END_COLOR . PHP_EOL);
    }
  }
}
