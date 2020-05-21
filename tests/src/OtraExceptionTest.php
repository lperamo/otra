<?php
declare(strict_types=1);

namespace src;

use otra\OtraException;
use phpunit\framework\TestCase;
use ReflectionException;

/**
 * @runTestsInSeparateProcesses
 */
class OtraExceptionTest extends TestCase
{
  private const BUNDLES_FOLDER = BASE_PATH . 'bundles/',
    BUNDLES_CONFIG_FOLDER = self::BUNDLES_FOLDER . 'config/',
    BUNDLES_CONFIG_ROUTES = self::BUNDLES_CONFIG_FOLDER . 'Routes.php';

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER['APP_ENV'] = 'prod';

    // Adding test bundle routes config in "bundles/config" if nothing exists
    if (file_exists(self::BUNDLES_CONFIG_FOLDER) === false)
    {
      mkdir(self::BUNDLES_CONFIG_FOLDER, 0777, true);
      file_put_contents(
        self::BUNDLES_CONFIG_ROUTES,
        '<?php return []; ?>'
        //return ['HelloWorld'=>['chunks'=>['/helloworld','HelloWorld','frontend','index','HomeAction'],'resources'=>['template'=> true ]]];
      );
    }
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    // If we are working on the framework itself
    if (OTRA_PROJECT === false)
    {
      unlink(self::BUNDLES_CONFIG_ROUTES);

      if (!file_exists(self::BUNDLES_CONFIG_FOLDER))
        rmdir(self::BUNDLES_CONFIG_FOLDER);

      if (!file_exists(self::BUNDLES_FOLDER))
        rmdir(self::BUNDLES_FOLDER);
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
    removeMethodScopeProtection(OtraException::class, 'errorMessage')
      ->invokeArgs($exception, []);
  }

  /**
   * @throws ReflectionException
   * @author Lionel Péramo
   */
  public function testOtraException_WithContext(): void
  {
    $exception = new OtraException('test');

    /* We cannot force the PHP_SAPI constant so it will launch OtraExceptionCLI but we can workaround it.
     * We launch it this way anyway but we manually set the context after in order to not be overwritten by the
     * OtraExceptionCLI class. */
    $exception->context = ['variables' => []];

    self::assertInstanceOf(OtraException::class, $exception);
    removeMethodScopeProtection(OtraException::class, 'errorMessage')
      ->invokeArgs($exception, []);
  }
}
