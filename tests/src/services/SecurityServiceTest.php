<?php
declare(strict_types=1);

namespace src\services;

use Exception;
use otra\{MasterController, OtraException};
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SecurityServiceTest extends TestCase
{
  private const
    CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC = "Content-Security-Policy: frame-ancestors 'none';",
    DOT_PHP = '.php',
    DIRECTIVES = 1,
    ENV_DEV = 'dev',
    ENV_PROD = 'prod',
    ROUTE = 'route',
    TEST_SECURITY_PATH = TEST_PATH . 'security/',
    TEST_SECURITY_DEV_PATH = self::TEST_SECURITY_PATH . self::ENV_DEV . '/',
    TEST_SECURITY_PROD_PATH = self::TEST_SECURITY_PATH . self::ENV_PROD . '/',
    ROUTE_SECURITY_DEV_BASE_PATH = self::TEST_SECURITY_DEV_PATH . self::ROUTE,
    ROUTE_SECURITY_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . self::DOT_PHP,
    ROUTE_SECURITY_EMPTY_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'Empty' . self::DOT_PHP,
    ROUTE_SECURITY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . self::DOT_PHP,
    ROUTE_SECURITY_EMPTY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . 'Empty' . self::DOT_PHP,
    SECURITY_SERVICE = CORE_PATH . 'services/securityService.php',
    ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'EmptyString.php';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * Use of blocks without override
   *
   * @author Lionel Péramo
   * @throws Exception
   */
  public function testGetRandomNonceForCSP() : void
  {
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP();

    self::assertIsString($nonce);
    self::assertMatchesRegularExpression('@\w{64}@', $nonce);
  }

  public function testCreatePolicy_Dev_ContentSecurityPolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching
    $returnArray = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_DEV_FILE_PATH,
      CONTENT_SECURITY_POLICY[self::ENV_DEV]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      array_merge(
        CONTENT_SECURITY_POLICY[self::ENV_DEV],
        (require self::ROUTE_SECURITY_DEV_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY]
      ),
      $returnArray[self::DIRECTIVES]
    );
  }

  public function testCreatePolicy_Dev_NoContentSecurityPolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching
    $returnArray = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_DEV_BASE_PATH . 'MissingPolicy.php',
      CONTENT_SECURITY_POLICY[self::ENV_DEV]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'self'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      CONTENT_SECURITY_POLICY[self::ENV_DEV],
      $returnArray[self::DIRECTIVES]
    );
  }

  public function testCreatePolicy_Dev_EmptyContentSecurityPolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;
    $cspDevPolicyReworked = CONTENT_SECURITY_POLICY[self::ENV_DEV];
    unset($cspDevPolicyReworked[OTRA_KEY_SCRIPT_SRC_DIRECTIVE]);

    // launching
    $returnArray = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH,
      CONTENT_SECURITY_POLICY[self::ENV_DEV]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'self'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      $cspDevPolicyReworked,
      $returnArray[self::DIRECTIVES]
    );
  }

  public function testCreatePolicy_Prod_ContentSecurityPolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;

    // launching
    $returnArray = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_PROD_FILE_PATH,
      CONTENT_SECURITY_POLICY[self::ENV_PROD]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'none'; child-src 'self'; manifest-src 'self'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      array_merge(
        CONTENT_SECURITY_POLICY[self::ENV_PROD],
        (require self::ROUTE_SECURITY_PROD_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY]
      ),
      $returnArray[self::DIRECTIVES]
    );
  }

  public function testCreatePolicy_Dev_FeaturePolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching ['csp']
    $returnArray = createPolicy(
      OTRA_KEY_FEATURE_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_DEV_FILE_PATH,
      FEATURE_POLICY[self::ENV_DEV]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Feature-Policy: layout-animations 'self'; legacy-image-formats 'none'; oversized-images 'none'; sync-script 'none'; sync-xhr 'none'; unoptimized-images 'none'; unsized-media 'none'; accelerometer 'self'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      array_merge(
        FEATURE_POLICY[self::ENV_DEV],
        (require self::ROUTE_SECURITY_DEV_FILE_PATH)[OTRA_KEY_FEATURE_POLICY]
      ),
      $returnArray[self::DIRECTIVES]
    );
  }

  public function testCreatePolicy_Prod_FeaturePolicy() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;

    // launching
    $returnArray = createPolicy(
      OTRA_KEY_FEATURE_POLICY,
      self::ROUTE,
      self::ROUTE_SECURITY_PROD_FILE_PATH,
      FEATURE_POLICY[self::ENV_PROD]
    );

    // testing
    self::assertIsArray($returnArray);
    self::assertEquals(
      "Feature-Policy: accelerometer 'none'; ambient-light-sensor 'none'; ",
      $returnArray[OTRA_POLICY]
    );
    self::assertEquals(
      array_merge(
        FEATURE_POLICY[self::ENV_PROD],
        (require self::ROUTE_SECURITY_PROD_FILE_PATH)[OTRA_KEY_FEATURE_POLICY]
      ),
      $returnArray[self::DIRECTIVES]
    );
  }

  /**
   * @depends testHandleStrictDynamic_Dev_WithScriptSrc_NoStrictDynamic_NoNonces
   * @depends testCreatePolicy_Dev_ContentSecurityPolicy
   * @throws OtraException
   */
  public function testAddCspHeader_Dev() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching
    addCspHeader(self::ROUTE, self::ROUTE_SECURITY_DEV_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; script-src 'self' otra.tech ;style-src 'self' ;",
      $headers[0]
    );
  }

  /**
   * @depends testHandleStrictDynamic_Prod_WithScriptSrc_NoStrictDynamic_NoNonces
   * @depends testHandleStrictDynamic_Prod_WithStyleSrc_NoStrictDynamic_NoNonces
   * @depends testCreatePolicy_Prod_ContentSecurityPolicy
   * @throws OtraException
   */
  public function testAddCspHeader_Prod() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;

    // launching
    addCspHeader(self::ROUTE, self::ROUTE_SECURITY_PROD_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals(
      "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'none'; child-src 'self'; manifest-src 'self'; script-src 'none' ;style-src 'none' ;",
      $headers[0]
    );
  }

  /**
   * @depends testCreatePolicy_Dev_FeaturePolicy
   */
  public function testAddFeaturePoliciesHeader_Dev() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    require self::SECURITY_SERVICE;

    // launching
    addFeaturePoliciesHeader(self::ROUTE, self::ROUTE_SECURITY_DEV_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals(
      "Feature-Policy: layout-animations 'self'; legacy-image-formats 'none'; oversized-images 'none'; sync-script 'none'; sync-xhr 'none'; unoptimized-images 'none'; unsized-media 'none'; accelerometer 'self';",
      $headers[0]
    );
  }

  /**
   * @depends testCreatePolicy_Prod_FeaturePolicy
   */
  public function testAddFeaturePoliciesHeader_Prod() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    require self::SECURITY_SERVICE;

    // launching
    addFeaturePoliciesHeader(self::ROUTE, self::ROUTE_SECURITY_PROD_FILE_PATH);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertEquals("Feature-Policy: accelerometer 'none'; ambient-light-sensor 'none';", $headers[0]);
  }

  /**
   * If we do not have the related directive, then we put the default value from MasterController.
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Dev_WithoutScriptSrc() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_EMPTY_DEV_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals(
      "Content-Security-Policy: frame-ancestors 'none';script-src 'self' ;",
      $cspPolicy
    );
  }

  /**
   * If we do not have a defined policy, we put the default directives for this policy.
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Dev_WithScriptSrc_NoStrictDynamic_NoNonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_DEV_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals("Content-Security-Policy: frame-ancestors 'none';script-src 'self' otra.tech ;", $cspPolicy);
  }

  /**
   * If we have an empty policy, we simply remove that directive.
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Dev_WithEmptyScriptSrc_NoStrictDynamic_NoNonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals("Content-Security-Policy: frame-ancestors 'none';", $cspPolicy);
  }

  /**
   * If we have nonces with a script-src policy that does not contain 'strict-dynamic' mode, we must throw an exception.
   * Beware, here we only test 'script-src'.
   *
   * @depends testGetRandomNonceForCSP
   * @throws OtraException
   * @throws Exception
   */
  public function testHandleStrictDynamic_Dev_WithEmptyScriptSrc_NoStrictDynamic_Nonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP();

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_DEV_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals("Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' 'self' otra.tech 'nonce-" . $nonce . "';", $cspPolicy);
  }

  /**
   * If we have an empty policy, we simply remove that directive.
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Dev_WithEmptyScriptSrc_StrictDynamic_Nonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_DEV;
    $cspPolicy = "Content-Security-Policy: frame-ancestors 'none';";
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP();
    $nonce2 = getRandomNonceForCSP();

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_DEV_BASE_PATH . 'StrictDynamic.php')[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals(
      "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' 'nonce-" . $nonce . "' 'nonce-" . $nonce2 . "';",
      $cspPolicy
    );
  }

  /**
   * If we do not have the related directive, then we put the default value from MasterController.
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Prod_WithoutScriptSrc() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_EMPTY_PROD_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals(
      "Content-Security-Policy: frame-ancestors 'none';script-src 'self' ;",
      $cspPolicy
    );
  }

  /**
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Prod_WithScriptSrc_NoStrictDynamic_NoNonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_PROD_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals("Content-Security-Policy: frame-ancestors 'none';script-src 'none' ;", $cspPolicy);
  }

  /**
   * Beware, here we only test 'script-src'.
   *
   * @throws OtraException
   */
  public function testHandleStrictDynamic_Prod_WithStyleSrc_NoStrictDynamic_NoNonces() : void
  {
    // context
    $_SERVER[APP_ENV] = self::ENV_PROD;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      OTRA_KEY_STYLE_SRC_DIRECTIVE,
      $cspPolicy,
      (require self::ROUTE_SECURITY_PROD_FILE_PATH)[OTRA_KEY_CONTENT_SECURITY_POLICY],
      self::ROUTE
    );

    // testing
    self::assertEquals("Content-Security-Policy: frame-ancestors 'none';style-src 'none' ;", $cspPolicy);
  }
}
