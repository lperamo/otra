<?php
declare(strict_types=1);

use otra\{MasterController, OtraException};

if (!function_exists('getRandomNonceForCSP'))
{
  define('OTRA_KEY_FEATURE_POLICY', 'featurePolicy');
  define('OTRA_KEY_CONTENT_SECURITY_POLICY', 'csp');
  define('OTRA_KEY_SCRIPT_SRC_DIRECTIVE', 'script-src');
  define('OTRA_KEY_STYLE_SRC_DIRECTIVE', 'style-src');
  define('OTRA_LABEL_SECURITY_NONE', "'none'");
  define('OTRA_LABEL_SECURITY_SELF', "'self'");
  define('OTRA_LABEL_SECURITY_STRICT_DYNAMIC', "'strict-dynamic'");
  define('OTRA_POLICY', 0);
  define('OTRA_POLICIES', [
    OTRA_KEY_FEATURE_POLICY => 'Feature-Policy: ',
    OTRA_KEY_CONTENT_SECURITY_POLICY => 'Content-Security-Policy: '
  ]);
  define('OTRA_STRLEN_SCRIPT_SRC', 10);

  /**
   * @param string $directive
   *
   * @return string
   * @throws Exception
   */
  function getRandomNonceForCSP(string $directive = 'script-src') : string
  {
    $nonce = bin2hex(random_bytes(32));
    MasterController::$nonces[$directive][] = $nonce;

    return $nonce;
  }

  /**
   * Generates the security policy that will be added to the HTTP header.
   * We do not keep script-src and style-src directives that will be handled in handleStrictDynamic function.
   *
   * @param string      $policy                  Can be 'csp' or 'featurePolicy'
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   * @param array       $defaultPolicyDirectives The default policy directives (csp or feature policy) from
   *                                             MasterController
   *
   * @return array
   */
  function createPolicy(
    string $policy,
    string $route,
    ?string $routeSecurityFilePath,
    array $defaultPolicyDirectives
  ) : array
  {
    $finalProcessedPolicies = $defaultPolicyDirectives;

    // OTRA routes are not secure with CSP and feature policies for the moment
    if (!str_contains($route, 'otra') && $routeSecurityFilePath !== null)
    {
      // Retrieve security instructions from the routes configuration file
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

    foreach ($finalProcessedPolicies as $directive => $value)
    {
      // script-src directive of the Content Security Policy receives a special treatment
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE || $directive === OTRA_KEY_STYLE_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . ' ' . $value . '; ';
    }

    return [$finalPolicy, $finalProcessedPolicies];
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   *
   * @throws OtraException
   */
  function addCspHeader(string $route, ?string $routeSecurityFilePath): void
  {
    if (!headers_sent())
    {
      [$policy, $cspDirectives] = createPolicy(
        OTRA_KEY_CONTENT_SECURITY_POLICY,
        $route,
        $routeSecurityFilePath,
        MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]]
      );

      handleStrictDynamic(OTRA_KEY_SCRIPT_SRC_DIRECTIVE, $policy, $cspDirectives, $route);
      handleStrictDynamic(OTRA_KEY_STYLE_SRC_DIRECTIVE, $policy, $cspDirectives, $route);
      header($policy);
    }
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   */
  function addFeaturePoliciesHeader(string $route, ?string $routeSecurityFilePath) : void
  {
    if (!headers_sent())
      header(createPolicy(
        OTRA_KEY_FEATURE_POLICY,
        $route,
        $routeSecurityFilePath,
        MasterController::$featurePolicy[$_SERVER[APP_ENV]]
      )[OTRA_POLICY]);
  }

  /**
   * Handles strict dynamic mode for CSP
   *
   * @param string $directive
   * @param string $policy
   * @param array  $cspDirectives
   * @param string $route
   *
   * @throws OtraException
   */
  function handleStrictDynamic(string $directive, string &$policy, array $cspDirectives, string $route) : void
  {
    // If the directive (eg. 'script-src') is not there, then we only use the defaults
    if (!isset($cspDirectives[$directive]))
      $policy .= $directive . ' ' . MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive] . ' ';
    elseif ($cspDirectives[$directive] !== '')
      $policy .= $directive . ' ' . $cspDirectives[$directive] . ' ';
    else
      return;

    // if a value is set for 'script-src' but do not have the 'strict-dynamic' mode enabled
    if (!str_contains($policy, OTRA_LABEL_SECURITY_STRICT_DYNAMIC)
      && !empty(MasterController::$nonces[$directive]) // if it has nonces
      && (
        (isset(\config\AllConfig::$debug) && \config\AllConfig::$debug) // is debug mode active ?
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
