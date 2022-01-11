<?php
declare(strict_types=1);

namespace src\console\deployment\genSitemap;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV, BASE_PATH, CONSOLE_PATH, PROD};

use function otra\console\architecture\createHelloWorld\createHelloWorld;
use function otra\console\deployment\genSitemap\genSitemap;
use const otra\console\SUCCESS;

/**
 * @runTestsInSeparateProcesses
 */
class GenSitemapTaskTest extends TestCase
{
  private const
    SITEMAP_PATH = BASE_PATH . 'web/sitemap.xml';

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require CONSOLE_PATH . 'architecture/createHelloWorld/createHelloWorldTask.php';
    createHelloWorld(
      [
        'otra.php',
        'createHelloWorld'
      ]
    );

    // launching
    ob_start();
    require CONSOLE_PATH . 'deployment/genSitemap/genSitemapTask.php';
    genSitemap();
    $output = ob_get_clean();

    // testing
    self::assertFileExists(self::SITEMAP_PATH);
    self::assertEquals(
      'Site map created' . SUCCESS,
      $output
    );
  }
}
