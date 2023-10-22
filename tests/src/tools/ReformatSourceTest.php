<?php
declare(strict_types=1);

namespace src\tools;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\CORE_PATH;
use function otra\tools\reformatSource;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ReformatSourceTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testReformatSource() : void
  {
    require CORE_PATH . 'tools/reformatSource.php';

    self::assertSame(
      '&lt;p&gt;Hi&lt;/p&gt;<br/>&lt;p&gt;Ha&lt;/p&gt;',
      reformatSource('<p>Hi</p><p>Ha</p>')
    );
  }
}
