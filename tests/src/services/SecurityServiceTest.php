<?php
declare(strict_types=1);

namespace src\services;

use JetBrains\PhpStorm\ArrayShape;
use Exception;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV, CORE_PATH, DEV, DIR_SEPARATOR, PROD, TEST_PATH};
use const otra\services\
{
  OTRA_KEY_CONTENT_SECURITY_POLICY,
  OTRA_KEY_PERMISSIONS_POLICY,
  OTRA_POLICY,
  PERMISSIONS_POLICY
};
use function otra\services\
{addCspHeader,
  addNonces,
  addPermissionsPoliciesHeader,
  createPolicy,
  getRandomNonceForCSP,
  getRoutePolicies,
  handleStrictDynamic};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SecurityServiceTest extends TestCase
{
  private const array
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
    ];
  
  private const int DIRECTIVES = 1;
  
  private const string 
    CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC = "Content-Security-Policy: frame-ancestors 'none';",
    DOT_PHP = '.php',
    EXPECTED_SECURITY_POLICY_EXAMPLE = "Content-Security-Policy: base-uri " . self::OTRA_LABEL_SECURITY_SELF .
      "; form-action " . self::OTRA_LABEL_SECURITY_SELF . "; frame-ancestors " . self::OTRA_LABEL_SECURITY_NONE .
      "; default-src " . self::OTRA_LABEL_SECURITY_NONE . "; font-src " . self::OTRA_LABEL_SECURITY_SELF . "; img-src " .
      self::OTRA_LABEL_SECURITY_SELF . "; object-src " . self::OTRA_LABEL_SECURITY_SELF . "; connect-src " .
      self::OTRA_LABEL_SECURITY_SELF . "; child-src " . self::OTRA_LABEL_SECURITY_SELF . "; manifest-src " .
      self::OTRA_LABEL_SECURITY_SELF . "; style-src " . self::OTRA_LABEL_SECURITY_SELF . "; ",
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
    ROUTE_SECURITY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . self::DOT_PHP,
    ROUTE_SECURITY_EMPTY_PROD_FILE_PATH = self::TEST_SECURITY_PROD_PATH . self::ROUTE . 'Empty' . self::DOT_PHP,
    SECURITY_SERVICE = CORE_PATH . 'services/securityService.php',
    ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'EmptyString.php',
    ROUTE_SECURITY_STYLE_SRC_DEV_FILE_PATH = self::ROUTE_SECURITY_DEV_BASE_PATH . 'StyleSrc.php';

  /**
   * Use of blocks without override
   *
   * @author Lionel Péramo
   * @throws Exception
   */
  public function testGetRandomNonceForCSP() : void
  {
    $_SERVER[APP_ENV] = 'test';
    require self::SECURITY_SERVICE;
    $nonce = getRandomNonceForCSP(self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE);

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
    $customPolicyDirectives = getRoutePolicies(self::ROUTE, $securityFilePath);
    $returnArray = createPolicy(
      self::OTRA_KEY_CONTENT_SECURITY_POLICY,
      $customPolicyDirectives[0],
      $customPolicyDirectives[1][self::OTRA_KEY_CONTENT_SECURITY_POLICY],
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
          'Content-Security-Policy: base-uri ' . self::OTRA_LABEL_SECURITY_SELF . '; form-action ' . self::OTRA_LABEL_SECURITY_SELF . '; frame-ancestors ' . self::OTRA_LABEL_SECURITY_NONE . '; default-src ' . self::OTRA_LABEL_SECURITY_NONE . '; font-src ' . self::OTRA_LABEL_SECURITY_SELF . '; img-src ' . self::OTRA_LABEL_SECURITY_SELF . '; object-src ' . self::OTRA_LABEL_SECURITY_SELF . '; connect-src ' . self::OTRA_LABEL_SECURITY_NONE . '; child-src ' . self::OTRA_LABEL_SECURITY_SELF . '; manifest-src ' . self::OTRA_LABEL_SECURITY_SELF . '; style-src ' . self::OTRA_LABEL_SECURITY_NONE . "; ",
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
    [$otraRoute, $customPolicyDirectives] = getRoutePolicies(self::ROUTE, $securityFilePath);
    $returnValue = createPolicy(
      OTRA_KEY_PERMISSIONS_POLICY,
      $otraRoute,
      $customPolicyDirectives[OTRA_KEY_PERMISSIONS_POLICY],
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
          "Permissions-Policy: sync-xhr=(),accelerometer=self"
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
        "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'self'; child-src 'self'; manifest-src 'self'; style-src 'self'; script-src 'self' otra.tech;"],
      PROD => [
        PROD,
        self::ROUTE_SECURITY_PROD_FILE_PATH,
        "Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'none'; default-src 'none'; font-src 'self'; img-src 'self'; object-src 'self'; connect-src 'none'; child-src 'self'; manifest-src 'self'; style-src 'none'; script-src 'none';"
      ],
    ];
  }

  /**
   * @depends testCreatePermissionsPolicy
   * @dataProvider addPermissionPoliciesHeaderDataProvider
   *
   * @param string  $environment
   * @param ?string $routeSecurityFilePath
   * @param string  $expectedHeader
   *
   * @return void
   */
  public function testAddPermissionPoliciesHeader(
    string $environment,
    string $route,
    ?string $routeSecurityFilePath,
    string $expectedHeader
  ): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    require self::SECURITY_SERVICE;

    // launching
    addPermissionsPoliciesHeader($route, $routeSecurityFilePath);

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
        self::ROUTE,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Permissions-Policy: sync-xhr=(),accelerometer=self"
      ],
      DEV . ' OTRA route and null for securityPath' => [
        DEV,
        'otra_logs',
        null,
        "Permissions-Policy: sync-xhr=()"
      ],
      DEV . ' and null for securityPath' => [
        DEV,
        self::ROUTE,
        null,
        "Permissions-Policy: sync-xhr=()"
      ],
      PROD => [
        PROD,
        self::ROUTE,
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
   * @param string $baseCspPolicy
   *
   * @throws Exception
   * @return void
   */
  public function testHandleStrictDynamic(
    string $environment,
    string $routeSecurityFilePath,
    string $expectedPolicy,
    string $baseCspPolicy = self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC
  ): void
  {
    // context
    $_SERVER[APP_ENV] = $environment;
    require self::SECURITY_SERVICE;

    // launching
    handleStrictDynamic(
      self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE,
      $baseCspPolicy,
      (require $routeSecurityFilePath)[OTRA_KEY_CONTENT_SECURITY_POLICY]
    );
    handleStrictDynamic(
      self::OTRA_KEY_STYLE_SRC_DIRECTIVE,
      $baseCspPolicy,
      (require $routeSecurityFilePath)[OTRA_KEY_CONTENT_SECURITY_POLICY]
    );

    // testing
    self::assertSame($expectedPolicy, $baseCspPolicy);
  }

  /**
   * When we say without `script-src`, for example, we mean in the user configuration not in the final configuration.
   * @return array<string, array<string, string, string, string, ?bool, ?string, ?bool>>
   */

  #[ArrayShape(
    [
      'Development - With script-src, no strict-dynamic' => "array",
      'Development - With empty script-src, no strict-dynamic' => "array",
      'Development - With empty script-src, strict-dynamic' => "array",
      'Development - With style-src, no strict-dynamic' => "array",
      'Production - Without script-src' => "array",
      'Production - With script-src, no strict-dynamic' => "array"
    ])] public static function handleStrictDynamicDataProvider(): array
  {
    return [
      'Development - With script-src, no strict-dynamic' =>
      [
        DEV,
        self::ROUTE_SECURITY_DEV_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'self' otra.tech;style-src 'self';"
      ],
      'Development - With empty script-src, no strict-dynamic' =>
      [
        DEV,
        self::ROUTE_SECURITY_EMPTY_STRING_DEV_FILE_PATH,
        self::CSP_POLICY_VALUE_WITHOUT_SCRIPT_SRC_NOR_STYLE_SRC . "style-src 'self';"
      ],
      'Development - With empty script-src, strict-dynamic' =>
      [
        DEV,
        self::ROUTE_SECURITY_DEV_BASE_PATH . 'StrictDynamic.php',
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic';style-src 'self';"
      ],
      'Development - With style-src, no strict-dynamic' =>
      [
        DEV,
        self::ROUTE_SECURITY_STYLE_SRC_DEV_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic';style-src 'self' otra.tech;"
      ],
      'Production - Without script-src' =>
      [
        PROD,
        self::ROUTE_SECURITY_EMPTY_PROD_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic';style-src 'self';"
      ],
      'Production - With script-src, no strict-dynamic' =>
      [
        PROD,
        self::ROUTE_SECURITY_PROD_FILE_PATH,
        "Content-Security-Policy: frame-ancestors 'none';script-src 'none';style-src 'none';"
      ]
    ];
  }

  /**
   * @dataProvider addNoncesDataProvider
   *
   * @param string   $expectedPolicy
   * @param int|null $nonces
   *
   * @throws Exception
   * @return void
   */
  public function testAddNonces(string $expectedPolicy, ?int $nonces = 0): void
  {
    // context
    $_SERVER[APP_ENV] = DEV;
    require self::SECURITY_SERVICE;

    if ($nonces > 0)
    {
      getRandomNonceForCSP(self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE);
      getRandomNonceForCSP(self::OTRA_KEY_STYLE_SRC_DIRECTIVE);

      if ($nonces > 1)
      {
        getRandomNonceForCSP(self::OTRA_KEY_SCRIPT_SRC_DIRECTIVE);
      }
    }

    $policy = "Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic'; style-src 'self'; style-src-attr 'self';";

    // launching
    addNonces($policy);

    // testing
    self::assertMatchesRegularExpression($expectedPolicy, $policy);
  }

  /**
   * @return array<string, array{0:string, 1?:int}>
   */
  #[ArrayShape(
    [
      'no nonces' => "string[]",
      'one nonce' => "array",
      'two nonces' => "array"
    ]
  )] public static function addNoncesDataProvider(): array
  {
    return
    [
      'no nonces' =>
      [
        "@Content-Security-Policy: frame-ancestors 'none';script-src 'strict-dynamic';@"
      ],
      'one nonce' =>
      [
        "@Content-Security-Policy: frame-ancestors 'none';script-src 'nonce-[a-fA-F0-9]{64}' 'strict-dynamic'; style-src 'nonce-[a-fA-F0-9]{64}' 'self';@",
        1
      ],
      'two nonces' =>
      [
        "@Content-Security-Policy: frame-ancestors 'none';script-src 'nonce-[a-fA-F0-9]{64}' 'nonce-[a-fA-F0-9]{64}' 'strict-dynamic'; style-src 'nonce-[a-fA-F0-9]{64}' 'self'; style-src-attr 'self';@",
        2
      ]
    ];
  }
}
