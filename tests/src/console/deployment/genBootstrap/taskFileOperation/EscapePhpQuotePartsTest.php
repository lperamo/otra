<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\escapeQuotesInPhpParts;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class EscapePhpQuotePartsTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testEscapeQuotesInPhpParts(): void
  {
    // context
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    $contentToParse = '<?php declare(strict_types=1);echo \'test\';?>';


    // launching
    escapeQuotesInPhpParts($contentToParse);

    // testing
    static::assertSame(
      '<?php declare(strict_types=1);echo \\\'test\\\';?>',
      $contentToParse
    );
  }
}
