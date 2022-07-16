<?php
declare(strict_types=1);

namespace src\console\deployment\genSitemap;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\
{APP_ENV, BASE_PATH, CONSOLE_PATH, PROD, TEST_PATH};

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

  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();

    // cleaning
    if (file_exists(self::SITEMAP_PATH))
      unlink(self::SITEMAP_PATH);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require CONSOLE_PATH . 'architecture/createHelloWorld/createHelloWorldTask.php';
    ob_start();
    createHelloWorld();
    ob_end_clean();

    // launching
    ob_start();
    require CONSOLE_PATH . 'deployment/genSitemap/genSitemapTask.php';
    genSitemap();
    $output = ob_get_clean();

    // testing
    self::assertEquals(
      'Site map created' . SUCCESS,
      $output
    );
    self::assertFileExists(self::SITEMAP_PATH);
    self::assertMatchesRegularExpression(
      '@<\?xml version="1.0" encoding="UTF-8"\?>
<urlset xmlns="https://www\.sitemaps\.org/schemas/sitemap/0\.9">
 {2}<url>
 {4}<loc>https://otra\.tech/helloworld</loc>
 {4}<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}</lastmod>
 {2}</url>
</urlset>@',
      file_get_contents(self::SITEMAP_PATH)
    );
  }
}
