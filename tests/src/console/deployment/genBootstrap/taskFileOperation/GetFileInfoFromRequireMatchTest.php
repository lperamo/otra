<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use function otra\console\deployment\genBootstrap\getFileInfoFromRequireMatch;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\phpOrHTMLIntoEval;

/**
 * @runTestsInSeparateProcesses
 */
class GetFileInfoFromRequireMatchTest extends TestCase
{
  private const
    FILENAME = 'filename.php',
    CONSTANT_PATH_CONSTANTS = 'otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * // Not sure of this test
   *
   * @author  Lionel Péramo
   * @Depends EvalPathVariablesTest::testNoVariables
   * @Depends EvalPathVariablesTest::testVariableReplacedNoTemplate()
   * @Depends EvalPathVariablesTest::testVariableCannotBeReplacedNoTemplate()
   * @Depends EvalPathVariablesTest::testIsTemplate()
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
