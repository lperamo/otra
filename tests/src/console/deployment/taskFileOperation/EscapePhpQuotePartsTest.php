<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\escapeQuotesInPhpParts;

/**
 * @runTestsInSeparateProcesses
 */
class EscapePhpQuotePartsTest extends TestCase
{
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   */
  public function testEscapeQuotesInPhpParts()
  {
    // context
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    $contentToParse = '<?php declare(strict_types=1);echo \'test\';?>';


    // launching
    escapeQuotesInPhpParts($contentToParse);

    // testing
    static::assertEquals(
      '<?php declare(strict_types=1);echo \\\'test\\\';?>',
      $contentToParse
    );
  }
}
