<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use function otra\console\deployment\genBootstrap\phpOrHTMLIntoEval;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class PhpOrHTMLIntoEvalTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testPhpFile() : void
  {
    // context
    $contentToAdd = '<?php declare(strict_types=1);echo \'test\'; ?>';

    // launching
    phpOrHTMLIntoEval($contentToAdd);

    // testing
    static::assertSame(
      'declare(strict_types=1);echo \'test\'; ',
      $contentToAdd
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testPhpFileNoEndTag() : void
  {
    // context
    $contentToAdd = '<?php declare(strict_types=1);echo \'test\';';

    // launching
    phpOrHTMLIntoEval($contentToAdd);

    // testing
    static::assertSame(
      'declare(strict_types=1);echo \'test\';<?php',
      $contentToAdd
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testHtmlFile() : void
  {
    // context
    $contentToAdd = '<div></div>';

    // launching
    phpOrHTMLIntoEval($contentToAdd);

    // testing
    static::assertSame(
      '?><div></div><?php',
      $contentToAdd
    );
  }
}
