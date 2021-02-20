<?php
declare(strict_types=1);

namespace otra;

/**
 * @package otra
 */
class MasterController {public static array $nonces = [];}
require BASE_PATH . 'config/AllConfig.php';
require CORE_PATH . 'services/securityService.php';
define('SECURITY_ROUTE_PATH', CACHE_PATH . 'php/security/' . $_SERVER[APP_ENV] . '/' . OTRA_ROUTE . '.php');
header('Content-Encoding: gzip');

addCspHeader(OTRA_ROUTE, SECURITY_ROUTE_PATH);
addFeaturePoliciesHeader(OTRA_ROUTE, SECURITY_ROUTE_PATH);

echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . OTRA_ROUTE . VERSION . 'che') . '.gz');
exit;
