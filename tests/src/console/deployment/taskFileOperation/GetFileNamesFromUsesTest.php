<?php
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap
{
  const VERBOSE = 2;
}

namespace src\console\deployment\taskFileOperation
{
  use phpunit\framework\TestCase;
  use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
  use function otra\console\deployment\genBootstrap\
  {getFileNamesFromUses, hasSyntaxErrors, phpOrHTMLIntoEval};
  use const otra\console\CLI_WARNING;
  use const otra\console\END_COLOR;

  /**
   * @runTestsInSeparateProcesses
   */
  class GetFileNamesFromUsesTest extends TestCase
  {
    private const LEVEL = 1;
    // fixes issues like when AllConfig is not loaded while it should be
    protected $preserveGlobalState = FALSE;

    protected function setUp(): void
    {
      parent::setUp();
      require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    }

    /**
     * Tests only ONE use statement at a time.
     *
     * @author Lionel Péramo
     * @Depends AnalyzeUseTokenTest::testRouterAlwaysIncluded()
     * @Depends AnalyzeUseTokenTest::testIsDevControllerTrait()
     * @Depends AnalyzeUseTokenTest::testIsProdControllerTrait()
     * @Depends AnalyzeUseTokenTest::testIsBlockSystem()
     * @Depends AnalyzeUseTokenTest::testHasSlashAtFirstAndExternalLibraryClass()
     */
    public function testGetFileNamesFromUses() : void
    {
      // context
      $contentToAdd = PHP_EOL . 'use test\test{firstTest, secondTest, thirdTest};';
      $filesToConcat = $parsedConstants = $parsedFiles = [];

      // launching
      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants
      );

      // testing
      $this->expectOutputString(
        CLI_WARNING . 'EXTERNAL LIBRARY CLASS : test\test\firstTest' . END_COLOR . PHP_EOL .
        CLI_WARNING . 'EXTERNAL LIBRARY CLASS : test\test\secondTest' . END_COLOR . PHP_EOL .
        CLI_WARNING . 'EXTERNAL LIBRARY CLASS : test\test\thirdTest' . END_COLOR . PHP_EOL
      );
      static::assertEquals([], $filesToConcat);
      static::assertEquals([], $parsedConstants);
      static::assertEquals([], $parsedFiles);
    }

    /**
     * Tests only ONE use statement at a time.
     *
     * @author Lionel Péramo
     * @Depends AnalyzeUseTokenTest::testRouterAlwaysIncluded()
     * @Depends AnalyzeUseTokenTest::testIsDevControllerTrait()
     * @Depends AnalyzeUseTokenTest::testIsProdControllerTrait()
     * @Depends AnalyzeUseTokenTest::testIsBlockSystem()
     * @Depends AnalyzeUseTokenTest::testHasSlashAtFirstAndExternalLibraryClass()
     */
    public function testGetFileNamesFromUses_withoutParentheses() : void
    {
      // context
      $contentToAdd = PHP_EOL . 'use test\test\fourthTest;';
      $filesToConcat = $parsedConstants = $parsedFiles = [];

      // launching
      getFileNamesFromUses(
        self::LEVEL,
        $contentToAdd,
        $filesToConcat,
        $parsedFiles,
        $parsedConstants
      );

      // testing
      $this->expectOutputString(CLI_WARNING . 'EXTERNAL LIBRARY CLASS : test\test\fourthTest' . END_COLOR . PHP_EOL);
      static::assertEquals([], $filesToConcat);
      static::assertEquals([], $parsedConstants);
      static::assertEquals([], $parsedFiles);
    }
  }
}

