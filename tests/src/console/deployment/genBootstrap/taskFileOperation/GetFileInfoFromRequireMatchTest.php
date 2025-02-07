<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use function otra\console\deployment\genBootstrap\getFileInfoFromRequireMatch;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\phpOrHTMLIntoEval;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GetFileInfoFromRequireMatchTest extends TestCase
{
  private const string
    FILENAME = 'filename.php',
    CONSTANT_PATH_CONSTANTS = 'otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS';

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/getFileInfoFromRequireMatch.php';
  }

  /**
   * // Not sure of this test
   *
   * @author  Lionel Péramo
   * @Depends ResolveInclusionPathTest::testNoVariables
   * @Depends ResolveInclusionPathTest::testVariableReplacedNoTemplate()
   * @Depends ResolveInclusionPathTest::testVariableCannotBeReplacedNoTemplate()
   * @Depends ResolveInclusionPathTest::testIsTemplate()
   * @throws OtraException
   */
  public function testGetFileInfoFromRequireMatch() : void
  {
    // context
    $trimmedMatch = '<?php declare(strict_types=1);require $test; ?>';
    define(self::CONSTANT_PATH_CONSTANTS, ['test' => 'value']);

    // launching
    [$fileContent, $isTemplate] = getFileInfoFromRequireMatch($trimmedMatch, self::FILENAME);

    // testing
    static::assertSame(
      '\'value\'',
      $fileContent
    );

    static::assertSame(
      false,
      $isTemplate
    );
  }
}
