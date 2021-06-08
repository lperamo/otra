<?php
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap
{
  const GEN_BOOTSTRAP_LINT = 1;
}

namespace src\console\deployment\genBootstrap\taskFileOperation
{
  use otra\OtraException;
  use phpunit\framework\TestCase;
  use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, TEST_PATH};
  use const otra\console\{CLI_ERROR, CLI_INFO, CLI_SUCCESS, END_COLOR};
  use function otra\console\deployment\genBootstrap\contentToFile;

  /**
   * @runTestsInSeparateProcesses
   */
  class ContentToFileTest extends TestCase
  {
    // fixes issues like when AllConfig is not loaded while it should be
    protected $preserveGlobalState = FALSE;
    private const
      RELATIVE_FINAL_FILE_PATH = 'tests/examples/deployment/finalFile.php',
      FINAL_FILE_PATH = BASE_PATH . self::RELATIVE_FINAL_FILE_PATH,
      NO_SYNTAX_ERRORS_FILE = TEST_PATH . 'examples/deployment/noSyntaxErrors.php',
      WITH_SYNTAX_ERRORS_FILE = TEST_PATH . 'examples/deployment/withSyntaxErrors.php',
      FINAL_CHECKINGS = PHP_EOL . CLI_INFO . 'FINAL CHECKINGS => ';

    protected function setUp(): void
    {
      parent::setUp();
      require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    }

    protected function tearDown(): void
    {
      parent::tearDown();
      unlink(self::FINAL_FILE_PATH);
    }

    /**
     * @author Lionel Péramo
     * @throws OtraException
     */
    public function testContentToFile_allGood()
    {
      // context
      define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);

      // launching
      contentToFile(
        file_get_contents(self::NO_SYNTAX_ERRORS_FILE),
        self::FINAL_FILE_PATH
      );

      // testing
      static::assertFileExists(self::FINAL_FILE_PATH);
      static::assertFileEquals(self::NO_SYNTAX_ERRORS_FILE, self::FINAL_FILE_PATH);
      $this->expectOutputString(
        PHP_EOL . self::FINAL_CHECKINGS . CLI_SUCCESS . '[CLASSIC SYNTAX]' .
        CLI_SUCCESS . '[NAMESPACES]' . END_COLOR . PHP_EOL
      );
    }

    /**
     * @author Lionel Péramo
     * @throws OtraException
     */
    public function testContentToFile_hasSyntaxErrors()
    {
      // context
      define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 0);

      // testing exceptions
      $this->expectException(OtraException::class);

      // launching
      contentToFile(
        file_get_contents(self::WITH_SYNTAX_ERRORS_FILE),
        self::FINAL_FILE_PATH
      );

      // testing
      static::assertFileExists(self::FINAL_FILE_PATH);
      static::assertFileEquals(self::WITH_SYNTAX_ERRORS_FILE, self::FINAL_FILE_PATH);
      $this->expectOutputString(
        self::FINAL_CHECKINGS . CLI_SUCCESS . PHP_EOL . PHP_EOL . CLI_ERROR .
        '[CLASSIC SYNTAX ERRORS in ' . self::RELATIVE_FINAL_FILE_PATH . '!]'. END_COLOR . PHP_EOL
      );
    }
  }
}
