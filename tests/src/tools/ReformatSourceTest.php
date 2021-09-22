<?php
declare(strict_types=1);

namespace src\tools;

use phpunit\framework\TestCase;
use const otra\cache\php\CORE_PATH;
use function otra\tools\reformatSource;

/**
 * @runTestsInSeparateProcesses
 */
class ReformatSourceTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   */
  public function testReformatSource() : void
  {
    require CORE_PATH . 'tools/reformatSource.php';

    self::assertEquals(
      '&lt;p&gt;Hi&lt;/p&gt;<br/>&lt;p&gt;Ha&lt;/p&gt;',
      reformatSource('<p>Hi</p><p>Ha</p>')
    );
  }
}
