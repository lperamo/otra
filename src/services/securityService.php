<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\services
 */

use config\AllConfig;
use otra\MasterController;

if (!function_exists('getRandomNonceForCSP'))
{
  define('OTRA_KEY_PERMISSIONS_POLICY', 'permissionsPolicy');
  define('OTRA_KEY_CONTENT_SECURITY_POLICY', 'csp');
  define('OTRA_KEY_SCRIPT_SRC_DIRECTIVE', 'script-src');
  define('OTRA_KEY_STYLE_SRC_DIRECTIVE', 'style-src');

  // condition because of the definitions in updateConfTask.php (tests will fail otherwise)
  if (!defined('OTRA_LABEL_SECURITY_NONE'))
  {
    define('OTRA_LABEL_SECURITY_NONE', "'none'");
    define('OTRA_LABEL_SECURITY_SELF', "'self'");
    define('OTRA_LABEL_SECURITY_STRICT_DYNAMIC', "'strict-dynamic'");
  }

  define('OTRA_POLICY', 0);
  define('OTRA_POLICIES', [
    OTRA_KEY_PERMISSIONS_POLICY => 'Permissions-Policy: ',
    OTRA_KEY_CONTENT_SECURITY_POLICY => 'Content-Security-Policy: '
  ]);
  define('OTRA_STRLEN_SCRIPT_SRC', 10);
  define('CSP_ARRAY', [
    'base-uri' => OTRA_LABEL_SECURITY_SELF,
    'form-action' => OTRA_LABEL_SECURITY_SELF,
    'frame-ancestors' => OTRA_LABEL_SECURITY_SELF,
    'default-src' => OTRA_LABEL_SECURITY_NONE,
    'font-src' => OTRA_LABEL_SECURITY_SELF,
    'img-src' => OTRA_LABEL_SECURITY_SELF,
    'object-src' => OTRA_LABEL_SECURITY_SELF,
    'connect-src' => OTRA_LABEL_SECURITY_SELF,
    'child-src' => OTRA_LABEL_SECURITY_SELF,
    'manifest-src' => OTRA_LABEL_SECURITY_SELF,
    OTRA_KEY_STYLE_SRC_DIRECTIVE => OTRA_LABEL_SECURITY_SELF,
    OTRA_KEY_SCRIPT_SRC_DIRECTIVE => OTRA_LABEL_SECURITY_SELF
  ]);
  define('CONTENT_SECURITY_POLICY', [
    DEV => CSP_ARRAY,
    PROD => CSP_ARRAY
  ]);

  // experimental with bad support
  // ('layout-animations', 'legacy-image-formats', 'oversized-images', 'unoptimized-images', 'unsized-media')
  define('PERMISSIONS_POLICY', [
    DEV =>
      [
        'interest-cohort' => '', // To avoid tracking from Google Floc
        'sync-xhr' => ''
      ],
    PROD => []
  ]);

  /**
   * @param string $directive
   *
   * @return string
   * @throws Exception
   */
  function getRandomNonceForCSP(string $directive = OTRA_KEY_SCRIPT_SRC_DIRECTIVE) : string
  {
    $nonce = bin2hex(random_bytes(32));
    MasterController::$nonces[$directive][] = $nonce;

    return $nonce;
  }

  /**
   * Generates the security policy that will be added to the HTTP header.
   * We do not keep script-src and style-src directives that will be handled in handleStrictDynamic function.
   *
   * @param string                $policy                  Can be 'csp' or 'permissionsPolicy'
   * @param string                $route
   * @param ?string               $routeSecurityFilePath
   * @param array<string, string> $defaultPolicyDirectives The default policy directives (csp or permissions policy)
   *                                                        from MasterController
   *
   * @return array{0: string, 1: array<string, string>}
   */
  #[\JetBrains\PhpStorm\ArrayShape([
    'string',
    'array'
  ])]
  function createPolicy(
    string $policy,
    string $route,
    ?string $routeSecurityFilePath,
    array $defaultPolicyDirectives
  ) : array
  {
    $finalProcessedPolicies = $defaultPolicyDirectives;

    // OTRA routes are not secure with CSP and permissions policies for the moment
    if (!str_contains($route, 'otra') && $routeSecurityFilePath !== null)
    {
      // Retrieve security instructions from the routes configuration file
      /** @var array<string,array<string,string>> $policiesFromUserConfig */
      $policiesFromUserConfig = require $routeSecurityFilePath;

      // Forces the policies to be an empty array for the rest of the algorithm
      if (!isset($policiesFromUserConfig[$policy]))
        $policiesFromUserConfig[$policy] = [];

      $customPolicyDirectives = $policiesFromUserConfig[$policy];

      if (empty($finalProcessedPolicies))
        $finalProcessedPolicies = $customPolicyDirectives;
      else
      {
        $common = array_intersect($finalProcessedPolicies, $customPolicyDirectives);
        $finalProcessedPolicies = array_merge($finalProcessedPolicies, $customPolicyDirectives);

        if (!empty($common))
        {
          foreach ($finalProcessedPolicies as $finalProcessedPolicyName => $finalProcessedPolicy)
          {
            if ($finalProcessedPolicy === '')
              unset($finalProcessedPolicies[$finalProcessedPolicyName]);
          }
        }
      }
    }

    $finalPolicy = OTRA_POLICIES[$policy];

    $policySeparator = ($policy === OTRA_KEY_CONTENT_SECURITY_POLICY) ? ' ' : '=(';

    foreach ($finalProcessedPolicies as $directive => $value)
    {
      // script-src directive of the Content Security Policy receives a special treatment
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE || $directive === OTRA_KEY_STYLE_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . $policySeparator . $value;

      if ($policy === OTRA_KEY_PERMISSIONS_POLICY)
        $finalPolicy .= '),';
      else
        $finalPolicy .= '; ';
    }

    if($policy === OTRA_KEY_PERMISSIONS_POLICY)
    {
      $finalPolicy = str_replace(
        [
          OTRA_LABEL_SECURITY_SELF,
          '(self)'
        ],
        [
          'self',
          'self'
        ],
        $finalPolicy
      );
      $finalPolicy = substr($finalPolicy, 0, -1);
    }

    return [$finalPolicy, $finalProcessedPolicies];
  }

  /**
   * @param string  $route
   * @param ?string $routeSecurityFilePath
   */
  function addCspHeader(string $route, ?string $routeSecurityFilePath): void
  {
    if (!headers_sent())
    {
      [$policy, $cspDirectives] = createPolicy(
        OTRA_KEY_CONTENT_SECURITY_POLICY,
        $route,
        $routeSecurityFilePath,
        CONTENT_SECURITY_POLICY[$_SERVER[APP_ENV]]
      );
      handleStrictDynamic(OTRA_KEY_SCRIPT_SRC_DIRECTIVE, $policy, $cspDirectives, $route);
      handleStrictDynamic(OTRA_KEY_STYLE_SRC_DIRECTIVE, $policy, $cspDirectives, $route);
      header($policy);
    }
  }

  /**
   * @param string  $route
   * @param ?string $routeSecurityFilePath
   */
  function addPermissionsPoliciesHeader(string $route, ?string $routeSecurityFilePath) : void
  {
    if (!headers_sent())
      header(createPolicy(
        OTRA_KEY_PERMISSIONS_POLICY,
        $route,
        $routeSecurityFilePath,
        PERMISSIONS_POLICY[$_SERVER[APP_ENV]]
      )[OTRA_POLICY]);
  }

  /**
   * Handles strict dynamic mode for CSP
   *
   * @param string $directive
   * @param string $policy
   * @param array{
   *    'base-uri'?:string,
   *    'form-action'?:string,
   *    'frame-ancestors'?:string,
   *    'default-src'?:string,
   *    'font-src'?:string,
   *    'img-src'?:string,
   *    'object-src'?:string,
   *    'connect-src'?:string,
   *    'child-src'?:string,
   *    'manifest-src'?:string,
   *    'style-src'?:string,
   *    'script-src'?:string
   *  } $cspDirectives
   * @param string $route
   */
  function handleStrictDynamic(string $directive, string &$policy, array $cspDirectives, string $route) : void
  {
    // If the directive (eg. 'script-src') is not there, then we only use the defaults
    if (!isset($cspDirectives[$directive]))
      $policy .= $directive . ' ' . CONTENT_SECURITY_POLICY[$_SERVER[APP_ENV]][$directive] . ' ';
    elseif ($cspDirectives[$directive] !== '')
      $policy .= $directive . ' ' . $cspDirectives[$directive] . ' ';
    else
      return;

    // if a value is set for 'script-src' but do not have the 'strict-dynamic' mode enabled
    if (!str_contains($policy, OTRA_LABEL_SECURITY_STRICT_DYNAMIC)
      && !empty(MasterController::$nonces[$directive]) // if it has nonces
      && (
        (isset(AllConfig::$debug) && AllConfig::$debug) // is debug mode active ?
        || $route === 'otra_exception'
      )
    )
    {
      // we insert 'strict-dynamic' in the 'script-src' or 'style-src' policy (depends on $directive).
      $policy = substr_replace(
        $policy,
        ' ' . OTRA_LABEL_SECURITY_STRICT_DYNAMIC,
        mb_strpos($policy, $directive) + OTRA_STRLEN_SCRIPT_SRC,
        0
      );
    }

    foreach (MasterController::$nonces[$directive] as $nonceKey => $nonce)
    {
      $policy .= '\'nonce-' . $nonce . '\'';

      if (array_key_last(MasterController::$nonces[$directive]) !== $nonceKey)
        $policy .= ' ';
    }

    $policy .= ';';
  }
}
