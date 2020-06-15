<?php
declare(strict_types=1);

use otra\MasterController;

/**
 * @return string
 * @throws Exception
 */
function getRandomNonceForCSP() : string
{
  $nonce = bin2hex(random_bytes(32));
  MasterController::$nonces[] = $nonce;

  return $nonce;
}

/**
 * @static
 * @param array  $policiesArray
 * @param string $policies
 */
function addFeaturePolicies(array $policiesArray, string &$policies) : void
{
  foreach ($policiesArray as $feature => &$policy)
  {
    $policies .= $feature . ' ' . $policy . ';';
  }
}

/**
 * @param string $route
 * @param string $routeSecurityFilePath
 */
function addFeaturePoliciesHeader(string $route, string $routeSecurityFilePath) : void
{
  // OTRA routes are not secure with CSP and feature policies for the moment
  if (false === strpos($route, 'otra')
    && isset($routeSecurityFilePath)
    && $routeSecurityFilePath)
  {
    // Retrieve security instructions from the routes configuration file
    if (!isset(MasterController::$routes))
      MasterController::$routes = require CACHE_PATH . 'php/security/' . $route . '.php';

    if (isset(MasterController::$routes['dev']['featurePolicy']))
      MasterController::$featurePolicy['dev'] = array_merge(MasterController::$featurePolicy['dev'], MasterController::$routes['dev']['featurePolicy']);

    if (isset(MasterController::$routes['prod']['featurePolicy']))
      MasterController::$featurePolicy['prod'] = array_merge(MasterController::$featurePolicy['prod'], MasterController::$routes['prod']['featurePolicy']);
  }

  $featurePolicies = '';

  if ($_SERVER[APP_ENV] === 'dev')
    addFeaturePolicies(
      MasterController::$featurePolicy['dev'],
      $featurePolicies
    );

  addFeaturePolicies(
    MasterController::$featurePolicy['prod'],
    $featurePolicies
  );

  header('Feature-Policy: ' . $featurePolicies);
}

/**
 * @param string $route
 * @param string $routeSecurityFilePath
 */
function addCspHeader(string $route, string $routeSecurityFilePath) : void
{
  // OTRA routes are not secure with CSP and feature policies for the moment
  if (false === strpos($route, 'otra')
    && isset($routeSecurityFilePath)
    && $routeSecurityFilePath)
  {
    // Retrieve security instructions from the routes configuration file
    if (!isset(MasterController::$routes))
      MasterController::$routes = require CACHE_PATH . 'php/security/' . $route . '.php';

    if (isset(MasterController::$routes['dev']['csp']))
      MasterController::$contentSecurityPolicy['dev'] = array_merge(
        MasterController::$contentSecurityPolicy['dev'],
        MasterController::$routes['dev']['csp']
      );

    if (isset(MasterController::$routes['prod']['csp']))
      MasterController::$contentSecurityPolicy['prod'] = array_merge(
        MasterController::$contentSecurityPolicy['prod'],
        MasterController::$routes['prod']['csp']
      );
  }

  $contentSecurityPolicy = 'Content-Security-Policy: ';

  foreach (MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]] as $directive => &$value)
  {
    $contentSecurityPolicy .= $directive . ' ' . $value . '; ';
  }

  // If no value has been set for 'script-src', we define automatically a secure policy for this directive
  if (!isset(MasterController::$contentSecurityPolicy[$_SERVER[APP_ENV]]['script-src']))
  {
    $contentSecurityPolicy .= 'script-src' . ' \'strict-dynamic\' ';

    foreach (MasterController::$nonces as &$nonce)
    {
      $contentSecurityPolicy .= '\'nonce-' . $nonce . '\' ';
    }
  }

  header($contentSecurityPolicy);
}
