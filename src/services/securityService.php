<?php
declare(strict_types=1);

use otra\MasterController;

if (!function_exists('getRandomNonceForCSP'))
{
  define('OTRA_KEY_DEVELOPMENT_ENVIRONMENT', 'dev');
  define('OTRA_KEY_PRODUCTION_ENVIRONMENT', 'prod');
  define('OTRA_KEY_FEATURE_POLICY', 'featurePolicy');
  define('OTRA_KEY_CONTENT_SECURITY_POLICY', 'csp');
  define('OTRA_KEY_SCRIPT_SRC_DIRECTIVE', 'script-src');
  define('OTRA_LABEL_SECURITY_NONE', "'none'");
  define('OTRA_LABEL_SECURITY_SELF', "'self'");
  define('POLICIES', [
    OTRA_KEY_FEATURE_POLICY => 'Feature-Policy: ',
    OTRA_KEY_CONTENT_SECURITY_POLICY => 'Content-Security-Policy: '
  ]);

  /**
   * @return string
   * @throws Exception
   */
  function getRandomNonceForCSP(): string
  {
    $nonce = bin2hex(random_bytes(32));
    MasterController::$nonces[] = $nonce;

    return $nonce;
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   */
  function addFeaturePoliciesHeader(string $route, ?string $routeSecurityFilePath): void
  {
    $policy = createPolicy(
      OTRA_KEY_FEATURE_POLICY,
      $route,
      $routeSecurityFilePath,
      MasterController::$featurePolicy
    );
    echo $policy;
    header($policy);
  }

  /**
   * @param string $policy
   * @param string $route
   * @param string $routeSecurityFilePath
   * @param array  $policyDirectives
   *
   * @throws \otra\OtraException
   */
  function createPolicy(string $policy, string $route, string $routeSecurityFilePath, array &$policyDirectives) : string
  {
    // OTRA routes are not secure with CSP and feature policies for the moment
    if (false === strpos($route, 'otra')
      && isset($routeSecurityFilePath)
      && $routeSecurityFilePath)
    {
      // Retrieve security instructions from the routes configuration file
      if (!isset(MasterController::$routesSecurity))
        MasterController::$routesSecurity = require CACHE_PATH . 'php/security/' . $route . '.php';

      // If we have a policy for the development environment, we use it
      if (isset(MasterController::$routesSecurity[OTRA_KEY_DEVELOPMENT_ENVIRONMENT][$policy]))
        $policyDirectives[OTRA_KEY_DEVELOPMENT_ENVIRONMENT] = array_merge(
          $policyDirectives[OTRA_KEY_DEVELOPMENT_ENVIRONMENT],
          MasterController::$routesSecurity[OTRA_KEY_DEVELOPMENT_ENVIRONMENT][$policy]
        );

      // If we have a policy for the production environment, we use it
      if (isset(MasterController::$routesSecurity[OTRA_KEY_PRODUCTION_ENVIRONMENT][$policy]))
        $policyDirectives[OTRA_KEY_PRODUCTION_ENVIRONMENT] = array_merge(
          $policyDirectives[OTRA_KEY_PRODUCTION_ENVIRONMENT],
          MasterController::$routesSecurity[OTRA_KEY_PRODUCTION_ENVIRONMENT][$policy]
        );
    }

    $finalPolicy = POLICIES[$policy];

    foreach ($policyDirectives[$_SERVER[APP_ENV]] as $directive => &$value)
    {
      // script-src directive of the Content Security Policy receives a special treatment
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . ' ' . $value . '; ';
    }

    return $finalPolicy;
  }

  /**
   * @param string      $route
   * @param string|null $routeSecurityFilePath
   *
   * @throws \otra\OtraException
   */
  function addCspHeader(string $route, ?string $routeSecurityFilePath): void
  {
    $policy = createPolicy(
      OTRA_KEY_CONTENT_SECURITY_POLICY,
      $route,
      $routeSecurityFilePath,
      MasterController::$contentSecurityPolicy
    );

    if ($policy === OTRA_KEY_CONTENT_SECURITY_POLICY)
    {
      if (!isset(MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][OTRA_KEY_SCRIPT_SRC_DIRECTIVE]))
      {
        $policy .= OTRA_KEY_SCRIPT_SRC_DIRECTIVE . ' \'strict-dynamic\' ';
      } elseif (strpos(
          MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][OTRA_KEY_SCRIPT_SRC_DIRECTIVE],
          '\'strict-dynamic\''
        ) === false) // if a value is set for 'script-src' but no 'strict-dynamic' mode
      {
        if (!empty(MasterController::$nonces)) // but has nonces
        {
          // adding nonces to avoid error loop before throwing the exception
          $policyDirectives[$_SERVER[APP_ENV]][OTRA_KEY_SCRIPT_SRC_DIRECTIVE] = "'self' 'strict-dynamic'";
          throw new \otra\OtraException('Content Security Policy error : you must have the mode \'strict-dynamic\' in the \'script-src\' directive for the route \'' . $route . '\' if you use nonces!');
        }

        header($policy . OTRA_KEY_SCRIPT_SRC_DIRECTIVE . ' ' .
          MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][OTRA_KEY_SCRIPT_SRC_DIRECTIVE] . ';');

        return;
      } else
        $policy .= OTRA_KEY_SCRIPT_SRC_DIRECTIVE . ' ' .
          MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]][OTRA_KEY_SCRIPT_SRC_DIRECTIVE] . ' ';

      foreach (MasterController::$nonces as &$nonce)
      {
        $policy .= '\'nonce-' . $nonce . '\' ';
      }
    }

    header($policy);
  }
}
