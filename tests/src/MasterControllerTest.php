<?php
declare(strict_types=1);
namespace tests\src;
use otra\MasterController;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use const otra\cache\php\{BASE_PATH, CORE_PATH};
use function otra\tools\removeFieldsScopeProtection;

/**
 * /!\ Beware the bundle HelloWorld will be erased in cleaning phase!
 *
 * @author Lionel Péramo
 * @runTestsInSeparateProcesses
 */
class MasterControllerTest extends TestCase
{
  private const string
    KEY_STYLESHEETS = 'stylesheets',
    KEY_JAVASCRIPTS = 'javaScripts',
    BASE_NAME_TEST_1_CSS = 'test.css',
    BASE_NAME_TEST_2_CSS = 'test2.css',
    BASE_NAME_TEST_3_CSS = 'test3.css',
    BASE_NAME_TEST_1_JS = 'test.js',
    BASE_NAME_TEST_2_JS = 'test2.js',
    BASE_NAME_TEST_3_JS = 'test3.js';

  /**
   * @throws ReflectionException
   */
  public function testGetCacheFileName()
  {
    self::assertSame(
      BASE_PATH . 'b8b627367b7342de2af53dfe7e4f85c3d2185eb3.cacheTest',
      new ReflectionMethod(
        MasterController::class,
        'getCacheFileName'
      )->invokeArgs(null, ['route', BASE_PATH, 'v1', '.cacheTest'])
    );
  }

  /**
   * @throws ReflectionException
   */
  public function testCss()
  {
    // context
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    $stylesheets = removeFieldsScopeProtection(MasterController::class, [self::KEY_STYLESHEETS])[self::KEY_STYLESHEETS];
    $stylesheets->setValue([self::BASE_NAME_TEST_1_CSS]);

    // launch
    new ReflectionMethod(
      MasterController::class,
      'css'
    )->invokeArgs(null, [[self::BASE_NAME_TEST_2_CSS, self::BASE_NAME_TEST_3_CSS], true]);

    // launching and testing
    self::assertSame(
      [self::BASE_NAME_TEST_1_CSS, self::BASE_NAME_TEST_2_CSS, self::BASE_NAME_TEST_3_CSS],
      $stylesheets->getValue()
    );
  }

  /**
   * @throws ReflectionException
   */
  public function testJs()
  {
    // context
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    $stylesheets = removeFieldsScopeProtection(MasterController::class, [self::KEY_JAVASCRIPTS])[self::KEY_JAVASCRIPTS];
    $stylesheets->setValue([self::BASE_NAME_TEST_1_JS]);

    // launch
    new ReflectionMethod(
      MasterController::class,
      'js'
    )->invokeArgs(null, [[self::BASE_NAME_TEST_2_JS, self::BASE_NAME_TEST_3_JS], true]);

    // launching and testing
    self::assertSame(
      [self::BASE_NAME_TEST_1_JS, self::BASE_NAME_TEST_2_JS, self::BASE_NAME_TEST_3_JS],
      $stylesheets->getValue()
    );
  }
}
