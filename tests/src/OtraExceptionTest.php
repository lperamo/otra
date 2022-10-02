<?php
declare(strict_types=1);

namespace src;

use otra\OtraException;
use phpunit\framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use const otra\cache\php\{APP_ENV, BUNDLES_PATH, OTRA_PROJECT, PROD};

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionTest extends TestCase
{
  private const
    BUNDLES_CONFIG_FOLDER = BUNDLES_PATH . 'config/',
    BUNDLES_CONFIG_ROUTES = self::BUNDLES_CONFIG_FOLDER . 'Routes.php';
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;

    // Adding test bundle routes config in "bundles/config" if nothing exists
    if (!file_exists(self::BUNDLES_CONFIG_FOLDER))
    {
      mkdir(self::BUNDLES_CONFIG_FOLDER, 0777, true);
      file_put_contents(
        self::BUNDLES_CONFIG_ROUTES,
        '<?php declare(strict_types=1); return [];'
      );
    }
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    // If we are working on the framework itself
    if (!OTRA_PROJECT)
    {
      if (file_exists(self::BUNDLES_CONFIG_ROUTES))
        unlink(self::BUNDLES_CONFIG_ROUTES);

      if (!file_exists(self::BUNDLES_CONFIG_FOLDER))
        rmdir(self::BUNDLES_CONFIG_FOLDER);

      if (!file_exists(BUNDLES_PATH))
        rmdir(BUNDLES_PATH);
    }
  }

  /**
   * @throws ReflectionException
   * @author Lionel Péramo
   */
  public function testOtraException(): void
  {
    $exception = new OtraException('test');
    self::assertInstanceOf(OtraException::class, $exception);
    (new ReflectionMethod(OtraException::class, 'errorMessage'))->invokeArgs($exception, []);
  }

  /**
   * @author Lionel Péramo
   * @throws ReflectionException|OtraException
   */
  public function testOtraException_WithContext(): void
  {
    /* We cannot force the PHP_SAPI constant, so it will launch OtraExceptionCli, but we can work around it.
     * We launch it this way anyway, but we manually set the context after in order to not be overwritten by the
     * OtraExceptionCli class. */
    $exception = new OtraException('test', null, '', null, ['variables' => []]);
    self::assertInstanceOf(OtraException::class, $exception);
    (new ReflectionMethod(OtraException::class, 'errorMessage'))->invokeArgs($exception, []);
  }
}
