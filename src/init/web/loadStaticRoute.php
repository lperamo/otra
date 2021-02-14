<?php
declare(strict_types=1);

namespace otra;

/**
 * @package otra
 */
class MasterController {
  public static array $nonces = [],
    $contentSecurityPolicy = [
    'dev' =>
      [
        'frame-ancestors' => "'self'",
        'default-src' => "'self'",
        'font-src' => "'self'",
        'img-src' => "'self'",
        'object-src' => "'self'",
        'connect-src' => "'self'",
        'child-src' => "'self'",
        'manifest-src' => "'self'",
        'style-src' => "'self'"
      ],
    'prod' => [] // assigned in the constructor
  ],
    $featurePolicy = [
    'dev' =>
      [
        'layout-animations' => "'self'",
        'legacy-image-formats' => "'none'",
        'oversized-images' => "'none'",
        'sync-script' => "'none'",
        'sync-xhr' => "'none'",
        'unoptimized-images' => "'none'",
        'unsized-media' => "'none'"
      ],
    'prod' => []
  ],
    $routesSecurity;
}
require BASE_PATH . 'config/AllConfig.php';
require CORE_PATH . 'services/securityService.php';
$securityRoutePath = CACHE_PATH . 'php/security/' . $_SERVER[APP_ENV] . '/' . OTRA_ROUTE . '.php';
header('Content-Encoding: gzip');

addCspHeader(OTRA_ROUTE, $securityRoutePath);
addFeaturePoliciesHeader(OTRA_ROUTE, $securityRoutePath);

echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . OTRA_ROUTE . VERSION . 'che') . '.gz');
exit;
