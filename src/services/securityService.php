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
  define('POLICIES', [
    OTRA_KEY_FEATURE_POLICY => 'Feature-Policy: ',
    OTRA_KEY_CONTENT_SECURITY_POLICY => 'Content-Security-Policy: '
  ]);

  /**
   * @param string $directive
   *
   * @return string
   * @throws Exception
   */
  function getRandomNonceForCSP(string $directive = 'script-src'): string
  {
    $nonce = bin2hex(random_bytes(32));
    MasterController::$nonces[$directive][] = $nonce;

    return $nonce;
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   */
  function addFeaturePoliciesHeader(string $route, ?string $routeSecurityFilePath): void
  {
    if (!headers_sent())
      header(createPolicy(
        OTRA_KEY_FEATURE_POLICY,
        $route,
        $routeSecurityFilePath,
        MasterController::$featurePolicy[$_SERVER[APP_ENV]]
      ));
  }

  /**
   * @param string      $policy
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   * @param array       $policyDirectives
   *
   * @return string
   */
  function createPolicy(string $policy, string $route, ?string $routeSecurityFilePath, array &$policyDirectives) : string
  {
    // OTRA routes are not secure with CSP and feature policies for the moment
    if (false === strpos($route, 'otra')
      && $routeSecurityFilePath !== null
      && $routeSecurityFilePath)
    {
      // Retrieve security instructions from the routes configuration file
      // /!\ Additional configuration could have been added for the debug toolbar
      $tempSecurity = isset(MasterController::$routesSecurity) ? MasterController::$routesSecurity : [];

      MasterController::$routesSecurity = require CACHE_PATH . 'php/security/' . $_SERVER[APP_ENV] . '/' . $route . '.php';

      MasterController::$routesSecurity = array_merge($tempSecurity, MasterController::$routesSecurity);

      // If we have a policy for this environment, we use it
      if (isset(MasterController::$routesSecurity[$policy]))
        $policyDirectives = array_merge($policyDirectives, MasterController::$routesSecurity[$policy]);
    }

    $finalPolicy = POLICIES[$policy];

    foreach ($policyDirectives as $directive => &$value)
    {
      // script-src directive of the Content Security Policy receives a special treatment
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE || $directive === OTRA_KEY_STYLE_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . ' ' . $value . '; ';
    }

    return $finalPolicy;
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   *
   * @throws OtraException
   */
  function addCspHeader(string $route, ?string $routeSecurityFilePath): void
  {
    if (headers_sent())
      return;

    $policy = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      $route,
      $routeSecurityFilePath,
      MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]]
    );

    handleStrictDynamic(OTRA_KEY_SCRIPT_SRC_DIRECTIVE, $policy, $route);
    handleStrictDynamic(OTRA_KEY_STYLE_SRC_DIRECTIVE, $policy, $route);
    header($policy);
  }

  /**
   * Handles strict dynamic mode for CSP
   *
   * @param string $directive
   * @param string $policy
   * @param string $route
   *
   * @throws OtraException
   */
  function handleStrictDynamic(string $directive, string &$policy, string $route) : void
  {
    if (!isset(MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive]))
    {
      $policy .= $directive . ' ' . OTRA_LABEL_SECURITY_STRICT_DYNAMIC . ' ';
    } elseif (strpos(
        MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive],
        OTRA_LABEL_SECURITY_STRICT_DYNAMIC
      ) === false) // if a value is set for 'script-src' but no 'strict-dynamic' mode
    {
      if (!empty(MasterController::$nonces[$directive])) // but has nonces
      {
        // adding nonces to avoid error loop before throwing the exception
        MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive] = OTRA_LABEL_SECURITY_SELF .
          ' ' . OTRA_LABEL_SECURITY_STRICT_DYNAMIC;

        // this 'if' also avoids a loop because there is no security rules for the exception page for now
        if ($route !== 'otra_exception')
          throw new OtraException(
            'Content Security Policy error : you must have the mode ' .
            OTRA_LABEL_SECURITY_STRICT_DYNAMIC . ' in the \'' . $directive . '\' directive for the route \'' .
            $route . '\' if you use nonces!'
          );
      }

      $policy .= $directive . ' ' .
        MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive] . ';';

      return;
    } else
      $policy .= $directive . ' ' .
        MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][$directive] . ' ';

    foreach (MasterController::$nonces[$directive] as &$nonce)
    {
      $policy .= '\'nonce-' . $nonce . '\' ';
    }

    $policy .= ';';
  }
}
