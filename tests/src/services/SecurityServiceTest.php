<?php
declare(strict_types=1);

namespace src\services;

use JetBrains\PhpStorm\ArrayShape;
use otra\config\AllConfig;
use Exception;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV, CORE_PATH, DEV, DIR_SEPARATOR, PROD, TEST_PATH};
use const otra\services\
{
  OTRA_KEY_CONTENT_SECURITY_POLICY,
  OTRA_KEY_PERMISSIONS_POLICY,
  OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
  OTRA_KEY_STYLE_SRC_DIRECTIVE,
  OTRA_POLICY,
  PERMISSIONS_POLICY
};
use function otra\services\
  {addCspHeader,addPermissionsPoliciesHeader,createPolicy,getRandomNonceForCSP,handleStrictDynamic};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SecurityServiceTest extends TestCase
{
  private const
    CONTENT_SECURITY_POLICY = [
      DEV => self::CSP_ARRAY,
      PROD => self::CSP_ARRAY
    ],
    CSP_ARRAY = [
      'base-uri' => self::OTRA_LABEL_SECURITY_SELF,
      'form-action' => self::OTRA_LABEL_SECURITY_SELF,
      'frame-ancestors' => self::OTRA_LABEL_SECURITY_NONE,
      'default-src' => self::OTRA_LABEL_SECURITY_NONE,
      'font-src' => self::OTRA_LABEL_SECURITY_SELF,
      'img-src' => self::OTRA_LABEL_SECURITY_SELF,
      'object-src' => self::OTRA_LABEL_SECURITY_SELF,
      'connect-src' => self::OTRA_LABEL_SECURITY_SELF,
      'child-src' => self::OTRA_LABEL_SECURITY_SELF,
      'manifest-src' => self::OTRA_LABEL_SECURITY_SELF,
      self::OTRA_KEY_STYLE_SRC_DIRECTIVE => self::OTRA_LABEL_SECURITY_SELF,
      self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE => self::OTRA_LABEL_SECURITY_SELF
    ],
    CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC = "Content-Security-Policy: frame-ancestors 'none';",
    DOT_PHP = '.php',
    DIRECTIVES = 1,
    EXPECTED_SECURITY_POLICY_EXAMPLE = "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; ",
    OTRA_KEY_CONTENT_SECURITY_POLICY = 'csp',
    OTRA_KEY_SCRIPT_SRC_DIRECTIVE = 'script-src',
    OTRA_KEY_STYLE_SRC_DIRECTIVE = 'style-src',
    OTRA_LABEL_SECURITY_NONE = "'none'",
    OTRA_LABEL_SECURITY_SELF = "'self'",
    ROUTE = 'route',
    TEST_SECURITY_PATH = TEST_PATH . 'security/',
    TEST_SECURITY_DEV_PATH = self::TEST_SECURITY_PATH . DEV . DIR_SEPARATOR,
    TEST_SECURITY_PROD_PATH = self::TEST_SECURITY_PATH . PROD . DIR_SEPARATOR,
    ROUTE_SECURITY_DEV_BASE_PATH = self::TEST_SECURITY_DEV_PATH . self::ROUTE,
    ROUTE_SECURITY_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . self::DOT_PHP,
    ROUTE_SECURITY_EMPTY_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'Empty' . self::DOT_PHP,
    ROUTE_SECURITY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . self::DOT_PHP,
    ROUTE_SECURITY_EMPTY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . 'Empty' . self::DOT_PHP,
    SECURITY_SERVICE = CORE_PATH . 'services/securityService.php',
    ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'EmptyString.php';

  /**
   * Use of blocks without override
   *
   * @author Lionel Péramo
   * @throws Exception
   */
  public function testGetRandomNonceForCSP() : void
  {
    $_SERVER[APP_ENV] = PROD;
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP();

    self::assertIsString($nonce);
    self::assertMatchesRegularExpression('@\w{64}@', $nonce);
  }

  /**
   * @dataProvider cspDataProvider
   *
   * @param string                $environment
   * @param string                $securityFilePath
   * @param string                $expectedPolicy
   * @param array<string, string> $expectedDirectives
   *
   * @return void
   */
  public function testCreateCspPolicy(
    string $environment,
    string $securityFilePath,
    string $expectedPolicy,
    array $expectedDirectives
  ): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    require self::SECURITY_SERVICE;

    // launching
    $returnArray = createPolicy(
      self::OTRA_KEY_CONTENT_SECURITY_POLICY,
      self::ROUTE,
      $securityFilePath,
      self::CONTENT_SECURITY_POLICY[$environment]
    );

    // testing
    self::assertIsArray($returnArray);
    // Checking the policy's string that will be added in the HTTP headers
    self::assertSame($expectedPolicy, $returnArray[OTRA_POLICY]);
    // Checking the policies array needed to handle the `strict-dynamic` rules for the CSP
    self::assertSame($expectedDirectives, $returnArray[self::DIRECTIVES]);
  }

  /**
   * @return array<array-key, array{0:string, 1:string}>
   */
  #[ArrayShape(
    [
      'Development - Content Security Policy' => "array",
      'Development - No Content Security Policy' => "array",
      'Development - Empty Content Security Policy' => "array",
      'Production - Content Security Policy' => "array"
    ])
  ] public static function cspDataProvider() : array
  {
    $cspDevPolicyReworked = self::CONTENT_SECURITY_POLICY[DEV];
    unset($cspDevPolicyReworked[self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE]);

    // testing
    return [
      'Development - Content Security Policy' =>
        [
          DEV,
          self::ROUTE_SECURITY_DEV_FILE_PATH,
          self::EXPECTED_SECURITY_POLICY_EXAMPLE,
          array_merge(
            self::CONTENT_SECURITY_POLICY[DEV],
            (require self::ROUTE_SECURITY_DEV_FILE_PATH)[self::OTRA_KEY_CONTENT_SECURITY_POLICY]
          )
        ],
      'Development - No Content Security Policy' =>
        [
          DEV,
          self::ROUTE_SECURITY_DEV_BASE_PATH . 'MissingPolicy.php',
          self::EXPECTED_SECURITY_POLICY_EXAMPLE,
          self::CONTENT_SECURITY_POLICY[DEV]
        ],
      'Development - Empty Content Security Policy' =>
        [
          DEV,
          self::ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH,
          self::EXPECTED_SECURITY_POLICY_EXAMPLE,
          $cspDevPolicyReworked
        ],
      'Production - Content Security Policy' =>
        [
          PROD,
          self::ROUTE_SECURITY_PROD_FILE_PATH,
          "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'none'; child-src 'self'; manifest-src 'self'; ",
          array_merge(
            self::CONTENT_SECURITY_POLICY[PROD],
            (require self::ROUTE_SECURITY_PROD_FILE_PATH)[self::OTRA_KEY_CONTENT_SECURITY_POLICY]
          )
        ],
    ];
  }

  /**
   * @dataProvider permissionsPolicyDataProvider
   *
   * @param string $environment
   * @param string $securityFilePath
   * @param string $expectedPolicy
   *
   * @return void
   */
  public function testCreatePermissionsPolicy(
    string $environment,
    string $securityFilePath,
    string $expectedPolicy
  ): void
  {
    // context
    $_SERVER[APP_ENV] = DEV;
    require self::SECURITY_SERVICE;

    // launching
    $returnValue = createPolicy(
      OTRA_KEY_PERMISSIONS_POLICY,
      self::ROUTE,
      $securityFilePath,
      PERMISSIONS_POLICY[$environment]
    );

    // testing
    self::assertIsString($returnValue);
    // Checking the policy's string that will be added in the HTTP headers
    self::assertSame($expectedPolicy, $returnValue);
  }

  /**
   * @return array<array-key, array{0:string, 1:string}>
   */
  #[ArrayShape([
    'Development - Permissions Policy' => "array",
    'Production - Permissions Policy' => "array"
  ])]
  public static function permissionsPolicyDataProvider() : array
  {
    return [
      'Development - Permissions Policy' =>
        [
          DEV,
          self::ROUTE_SECURITY_DEV_FILE_PATH,
          "Permissions-Policy: interest-cohort=(),sync-xhr=(),accelerometer=self"
        ],
      'Production - Permissions Policy' =>
        [
          PROD,
          self::ROUTE_SECURITY_PROD_FILE_PATH,
          "Permissions-Policy: accelerometer=(),ambient-light-sensor=()"
        ]
    ];
  }

  /**
   * @dataProvider cspHeaderDataProvider
   *
   * @param string $environment
   * @param string $routeSecurityFilePath
   * @param string $expectedHeader
   *
   * @return void
   */
  public function testCspHeader(string $environment, string $routeSecurityFilePath, string $expectedHeader): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    require self::SECURITY_SERVICE;

    // launching
    addCspHeader(self::ROUTE, $routeSecurityFilePath);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertSame($expectedHeader, $headers[0]);
  }

  /**
   * @return array<string, array<string, string, string>>
   */
  #[ArrayShape(
    [
      DEV => "array",
      PROD => "array"
    ]
  )]
  public static function cspHeaderDataProvider(): array
  {
    return [
      DEV => [
        DEV,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; script-src 'self' otra.tech ;style-src 'self' ;"],
      PROD => [
        PROD,
        self::ROUTE_SECURITY_PROD_FILE_PATH,
        "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'none'; child-src 'self'; manifest-src 'self'; script-src 'none' ;style-src 'none' ;"
      ],
    ];
  }

  /**
   * @depends testCreatePermissionsPolicy
   * @dataProvider
   *
   * @param string $environment
   * @param string $routeSecurityFilePath
   * @param string $expectedHeader
   *
   * @return void
   */
  public function testAddPermissionPoliciesHeader(
    string $environment,
    string $routeSecurityFilePath,
    string $expectedHeader
  ): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    require self::SECURITY_SERVICE;

    // launching
    addPermissionsPoliciesHeader(self::ROUTE, $routeSecurityFilePath);

    // testing
    $headers = xdebug_get_headers();
    self::assertNotEmpty($headers);
    self::assertCount(1, $headers);
    self::assertSame($expectedHeader, $headers[0]);
  }

  /**
   * @return array<string, array<string, string, string>>
   */
  #[ArrayShape(
    [
      DEV => "array",
      PROD => "array"
    ]
  )] public static function addPermissionPoliciesHeaderDataProvider(): array
  {
    return [
      DEV => [
        DEV,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Permissions-Policy: interest-cohort=(),sync-xhr=(),accelerometer=self"
      ],
      PROD => [
        PROD,
        self::ROUTE_SECURITY_PROD_FILE_PATH,
        "Permissions-Policy: accelerometer=(),ambient-light-sensor=()"
      ]
    ];
  }

  /**
   * @dataProvider handleStrictDynamicDataProvider
   *
   * @param string  $environment
   * @param string  $routeSecurityFilePath
   * @param string  $expectedPolicy
   * @param ?bool   $hasFirstNonce
   * @param ?bool   $hasSecondNonce
   *
   * @throws Exception
   * @return void
   */
  public function testHandleStrictDynamic(
    string $environment,
    string $routeSecurityFilePath,
    string $expectedPolicy,
    bool $hasFirstNonce = false,
    bool $hasSecondNonce = false
  ): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    $cspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC;
    require self::SECURITY_SERVICE;

    if ($hasFirstNonce)
      $firstNonce = getRandomNonceForCSP();

    if ($hasSecondNonce)
      $secondNonce = getRandomNonceForCSP();

    // launching
    handleStrictDynamic(
      $cspPolicy,
      (require $routeSecurityFilePath)[OTRA_KEY_CONTENT_SECURITY_POLICY][OTRA_KEY_SCRIPT_SRC_DIRECTIVE],
      self::ROUTE
    );

    // testing
    self::assertSame(
      $expectedPolicy .
        (!$hasFirstNonce ? '' : $firstNonce .
          (!$hasSecondNonce ? "';" : "' 'nonce-" . $secondNonce . "';")
        ),
      $cspPolicy
    );
  }

  /**
   * When we say without script-src for example, we mean in the user configuration not in the final configuration.
   * @return array<string, array<string, string, string, string, ?bool, ?string, ?bool>>
   */
  #[ArrayShape(
    [
      'Development - Without script-src' => "array",
      'Development - With script-src, no strict-dynamic, no nonces' => "array",
      'Development - With empty script-src, no strict-dynamic, no nonces' => "array",
      'Development - With empty script-src, no strict-dynamic, nonces' => "array",
      'Development - With empty script-src, strict-dynamic, 2 nonces' => "array",
      'Production - Without script-src, no nonces' => "array",
      'Production - With script-src, no strict-dynamic, no nonces' => "array",
    ]
  )]
  public static function handleStrictDynamicDataProvider(): array
  {
    return [
      // If we do not have the related directive, then we put the default value from MasterController.
      // Beware, here we only test 'script-src'.
      'Development - Without script-src' =>
      [
        DEV,
        self::ROUTE_SECURITY_EMPTY_DEV_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' ;"
      ],
      // If we do not have a defined policy, we put the default directives for this policy.
      // Beware, here we only test 'script-src'.
      'Development - With script-src, no strict-dynamic, no nonces' =>
      [
        DEV,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'self' otra.tech ;"
      ],
      // If we have an empty policy, we simply remove that directive.
      // Beware, here we only test 'script-src'.
      'Development - With empty script-src, no strict-dynamic, no nonces' =>
      [
        DEV,
        self::ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH,
        self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC
      ],
      // If we have nonces with a script-src policy that does not contain 'strict-dynamic' mode, we must throw an exception.
      // Beware, here we only test 'script-src'.
      'Development - With empty script-src, no strict-dynamic, nonces' =>
      [
        DEV,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' 'self' otra.tech 'nonce-",
        true
      ],
      // If we have an empty policy, we simply remove that directive.
      // Beware, here we only test 'script-src'.
      'Development - With empty script-src, strict-dynamic, 2 nonces' =>
      [
        DEV,
        self::ROUTE_SECURITY_DEV_BASE_PATH . 'StrictDynamic.php',
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' 'nonce-",
        true,
        true
      ],
      // If we do not have the related directive, then we put the default value from MasterController.
      // Beware, here we only test 'script-src'.
      'Production - Without script-src, no nonces' =>
      [
        PROD,
        self::ROUTE_SECURITY_EMPTY_PROD_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic' ;"
      ],
      // Beware, here we only test 'script-src'.
      'Production - With script-src, no strict-dynamic, no nonces' =>
      [
        PROD,
        self::ROUTE_SECURITY_PROD_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'none' ;"
      ]
    ];
  }
}
