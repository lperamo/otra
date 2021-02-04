<?php
declare(strict_types=1);

namespace src\services;

//use otra\Controller;
use otra\MasterController;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SecurityServiceTest extends TestCase
{
//  private static Controller $controller;
//  private const
//    LAYOUTS_PATH = TEST_PATH . 'src/bundles/views/',
//    BACKUPS_PATH = self::LAYOUTS_PATH . 'backups/';

//  protected function setUp(): void
//  {
//    parent::setUp();
//    $_SERVER[APP_ENV] = 'prod';
//    define('VERSION', 'v1');
//    $_SERVER['REQUEST_URI'] = '';
//    self::$controller = new Controller(
//      [
//        'pattern' => '',
//        'bundle' => '',
//        'module' => '',
//        'controller' => 'test',
//        'action' => 'testAction',
//        'route' => 'routeTest',
//        'hasJsToLoad' => false,
//        'hasCssToLoad' => false
//      ]
//    );
//  }
  private const ENV_DEV = 'dev';
  private const ENV_PROD = 'prod';
  private const TEST_SECURITY_PATH = TEST_PATH . 'security/';
  private const SECURITY_PATH = CACHE_PATH . 'security/';
  private const SECURITY_DEV_PATH = self::SECURITY_PATH . self::ENV_DEV . '/';
  private const SECURITY_PROD_PATH = self::SECURITY_PATH . self::ENV_PROD . '/';
  private const TEST_SECURITY_DEV_PATH = self::TEST_SECURITY_PATH . self::ENV_DEV . '/';
  private const TEST_SECURITY_PROD_PATH = self::TEST_SECURITY_PATH . self::ENV_PROD . '/';
  private const TEST_ROUTE = 'route';
  private const FEATURE_POLICY = 'featurePolicy';
  private const DOT_PHP = '.php';
  private const SECURITY_SERVICE = CORE_PATH . 'services/securityService.php';
  private const ROUTE_SECURITY_DEV_FILE_PATH = self::TEST_SECURITY_DEV_PATH . self::TEST_ROUTE . self::DOT_PHP;
  private const ROUTE_SECURITY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::TEST_ROUTE . self::DOT_PHP;

  /**
   * Use of blocks without override
   *
   * @author Lionel Péramo
   * @throws \Exception
   */
  public function testGetRandomNonceForCSP() : void
  {
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP();

    self::assertIsString($nonce);
    self::assertMatchesRegularExpression('@\w{64}@', $nonce);
  }

  public function testCreatePolicy_DevFeaturePolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching
    $policy = createPolicy(
      self::FEATURE_POLICY,
      self::TEST_ROUTE,
      self::ROUTE_SECURITY_DEV_FILE_PATH,
      MasterController::$featurePolicy[self::ENV_DEV]
    );

    // testing
    self::assertIsString($policy);
    self::assertEquals("Feature-Policy: layout-animations 'self'; legacy-image-formats 'none'; oversized-images 'none'; sync-script 'none'; sync-xhr 'none'; unoptimized-images 'none'; unsized-media 'none'; accelerometer 'self'; ", $policy);
  }

  public function testCreatePolicy_ProdFeaturePolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;

    // launching
    $policy = createPolicy(
      self::FEATURE_POLICY,
      self::TEST_ROUTE,
      self::ROUTE_SECURITY_PROD_FILE_PATH,
      MasterController::$featurePolicy[self::ENV_PROD]
    );

    // testing
    self::assertIsString($policy);
    self::assertEquals("Feature-Policy: accelerometer 'none'; ambient-light-sensor 'none'; autoplay 'none'; battery 'none'; camera 'none'; display-capture 'none'; document-domain 'none'; encrypted-media 'none'; execution-while-not-rendered 'none'; execution-while-out-of-viewport 'none'; fullscreen 'none'; geolocation 'none'; gyroscope 'none'; layout-animations 'self'; magnetometer 'none'; microphone 'none'; midi 'none'; navigation-override 'none'; payment 'none'; picture-in-picture 'self'; publickey-credentials-get 'none'; sync-script 'none'; sync-xhr 'none'; usb 'none'; wake-lock 'none'; xr-spatial-tracking 'none'; ", $policy);
  }

  public function testAddFeaturePoliciesHeader_Dev() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require CORE_PATH . 'services/securityService.php';

    // launching
    addFeaturePoliciesHeader('route', self::ROUTE_SECURITY_DEV_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals(
      "Feature-Policy: layout-animations 'self'; legacy-image-formats 'none'; oversized-images 'none'; sync-script 'none'; sync-xhr 'none'; unoptimized-images 'none'; unsized-media 'none'; accelerometer 'self';",
      $headers[0]
    );
  }

  public function testAddFeaturePoliciesHeader_Prod() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require CORE_PATH . 'services/securityService.php';

    // launching
    addFeaturePoliciesHeader('route', self::ROUTE_SECURITY_PROD_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals(
      "Feature-Policy: layout-animations 'self'; legacy-image-formats 'none'; oversized-images 'none'; sync-script 'none'; sync-xhr 'none'; unoptimized-images 'none'; unsized-media 'none'; accelerometer 'none'; ambient-light-sensor 'none'; autoplay 'none'; battery 'none'; camera 'none'; display-capture 'none'; document-domain 'none'; encrypted-media 'none'; execution-while-not-rendered 'none'; execution-while-out-of-viewport 'none'; fullscreen 'none'; geolocation 'none'; gyroscope 'none'; magnetometer 'none'; microphone 'none'; midi 'none'; navigation-override 'none'; payment 'none'; picture-in-picture 'self'; publickey-credentials-get 'none'; usb 'none'; wake-lock 'none'; xr-spatial-tracking 'none';",
      $headers[0]
    );
  }
}
