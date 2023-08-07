<?php
/**
 * @author  Lionel Péramo
 * @package otra\services
 */
declare(strict_types=1);

namespace otra\services;

use otra\config\AllConfig;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use otra\MasterController;
use const otra\cache\php\{APP_ENV,DEV,PROD};

const
  OTRA_KEY_PERMISSIONS_POLICY = 'permissionsPolicy',
  OTRA_KEY_CONTENT_SECURITY_POLICY = 'csp',
  OTRA_KEY_SCRIPT_SRC_DIRECTIVE = 'script-src',
  OTRA_KEY_STYLE_SRC_DIRECTIVE = 'style-src',
  OTRA_LABEL_SECURITY_NONE = "'none'",
  OTRA_LABEL_SECURITY_SELF = "'self'",
  OTRA_LABEL_SECURITY_STRICT_DYNAMIC = "'strict-dynamic'",
  OTRA_POLICY = 0,
  OTRA_POLICIES = [
    OTRA_KEY_PERMISSIONS_POLICY => 'Permissions-Policy: ',
    OTRA_KEY_CONTENT_SECURITY_POLICY => 'Content-Security-Policy: '
  ],
  OTRA_ROUTES_PREFIX = 'otra',
  CSP_ARRAY = [
    'base-uri' => OTRA_LABEL_SECURITY_SELF,
    'form-action' => OTRA_LABEL_SECURITY_SELF,
    'frame-ancestors' => OTRA_LABEL_SECURITY_NONE,
    'default-src' => OTRA_LABEL_SECURITY_NONE,
    'font-src' => OTRA_LABEL_SECURITY_SELF,
    'img-src' => OTRA_LABEL_SECURITY_SELF,
    'object-src' => OTRA_LABEL_SECURITY_SELF,
    'connect-src' => OTRA_LABEL_SECURITY_SELF,
    'child-src' => OTRA_LABEL_SECURITY_SELF,
    'manifest-src' => OTRA_LABEL_SECURITY_SELF,
    OTRA_KEY_STYLE_SRC_DIRECTIVE => OTRA_LABEL_SECURITY_SELF,
    OTRA_KEY_SCRIPT_SRC_DIRECTIVE => OTRA_LABEL_SECURITY_STRICT_DYNAMIC
  ],
  CONTENT_SECURITY_POLICY = [
    DEV => CSP_ARRAY,
    PROD => CSP_ARRAY
  ],
  // experimental with bad support
  // ('layout-animations', 'legacy-image-formats', 'oversized-images', 'unoptimized-images', 'unsized-media')
  PERMISSIONS_POLICY = [
    DEV =>
      [
        'sync-xhr' => ''
      ],
    PROD => []
  ];

if (!function_exists(__NAMESPACE__ . '\\getRandomNonceForCSP'))
{
  // We handle the edge case of the blocks.php file that is included via a template and needs MasterController,
  // allowing the block.php file of the template engine system to work in production mode,
  // by creating a class alias. Disabled when passing via the command line tasks.
  if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli' && !defined('otra\\OTRA_STATIC'))
    class_alias('otra\cache\php\AllConfig', 'otra\config\AllConfig');

  /**
   *
   * @throws Exception
   */
  function getRandomNonceForCSP(?string $directive = OTRA_KEY_SCRIPT_SRC_DIRECTIVE) : string
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
   *                                                       from MasterController
   *
   * @return array{0: string, 1: array<string, string>}|string The string is the policy that will be included in
   * the HTTP headers, the array is the array of policies needed to handle the `strict-dynamic` rules for the CSP
   * policies
   */
  function createPolicy(
    string $policy,
    string $route,
    ?string $routeSecurityFilePath,
    array $defaultPolicyDirectives
  ) : array|string
  {
    $finalProcessedPolicies = $defaultPolicyDirectives;

    // OTRA routes are not secure with CSP and permissions policies for the moment
    if (!str_contains($route, OTRA_ROUTES_PREFIX) && $routeSecurityFilePath !== null)
    {
      // Retrieve security instructions from the routes' configuration file
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
        $finalProcessedPolicies = [...$finalProcessedPolicies, ...$customPolicyDirectives];

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
      // script-src directive of the Content Security Policy receives a special treatment related to `strict-dynamic`
      // rule
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . $policySeparator . $value . ($policy === OTRA_KEY_PERMISSIONS_POLICY ? '),' : '; ');
    }

    if ($policy === OTRA_KEY_PERMISSIONS_POLICY)
    {
      return substr(
        str_replace(
          [
            OTRA_LABEL_SECURITY_SELF,
            '(self)'
          ],
          [
            'self',
            'self'
          ],
          $finalPolicy
        ),
        0,
        -1
      );
    }

    return [$finalPolicy, $finalProcessedPolicies];
  }

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
      handleStrictDynamic(OTRA_KEY_SCRIPT_SRC_DIRECTIVE, $policy, $cspDirectives);
      handleStrictDynamic(OTRA_KEY_STYLE_SRC_DIRECTIVE, $policy, $cspDirectives);
      addNonces($policy);
      header($policy);
    }
  }

  function addPermissionsPoliciesHeader(string $route, ?string $routeSecurityFilePath) : void
  {
    if (!headers_sent())
      header(createPolicy(
        OTRA_KEY_PERMISSIONS_POLICY,
        $route,
        $routeSecurityFilePath,
        PERMISSIONS_POLICY[$_SERVER[APP_ENV]]
      ));
  }

  function addNonces(string &$policy) : void
  {
    $directives = [OTRA_KEY_SCRIPT_SRC_DIRECTIVE, OTRA_KEY_STYLE_SRC_DIRECTIVE];

    foreach ($directives as $directive)
    {
      if (!empty(MasterController::$nonces[$directive]))
      {
        $nonceStr = '';

        foreach (MasterController::$nonces[$directive] as $nonce)
        {
          $nonceStr .= ' \'nonce-' . $nonce . '\'';
        }

        // Adds nonces to the related directive
        $policy = str_replace($directive, $directive . $nonceStr, $policy);
      }
    }
  }

  /**
   * Handles strict dynamic mode for CSP
   *
   * @param string  $directive     'strict-src' or 'style-src'
   * @param string  $policy
   * @param array   $cspDirectives Content Security Policy directives
   */
  function handleStrictDynamic(string $directive, string &$policy, array $cspDirectives) : void
  {
    // If the directive (e.g. 'script-src') is not there, then we only use the defaults
    if (!isset($cspDirectives[$directive]))
      $policy .= $directive . ' ' . CONTENT_SECURITY_POLICY[$_SERVER[APP_ENV]][$directive];
    elseif ($cspDirectives[$directive] !== '')
      $policy .= $directive . ' ' . $cspDirectives[$directive];
    else
      return;

    $policy .= ';';
  }
}
