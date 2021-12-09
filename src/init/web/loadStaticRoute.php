<?php
declare(strict_types=1);

namespace otra;

use const otra\cache\php\{APP_ENV, BASE_PATH, CACHE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\config\VERSION;
use const otra\web\OTRA_ROUTE;
use function otra\services\{addCspHeader,addPermissionsPoliciesHeader};

/**
 * @package otra
 */
class MasterController {public static array $nonces = [];}
require BASE_PATH . 'config/AllConfig.php';
require CORE_PATH . 'services/securityService.php';
define(
  __NAMESPACE__ . '\\TMP_SECURITY_ROUTE_PATH',
  CACHE_PATH . 'php/security/' . $_SERVER[APP_ENV] . DIR_SEPARATOR . OTRA_ROUTE . '.php'
);
define(__NAMESPACE__ . '\\SECURITY_ROUTE_PATH', file_exists(TMP_SECURITY_ROUTE_PATH) ? TMP_SECURITY_ROUTE_PATH : null);
header('Content-Encoding: gzip');

addCspHeader(OTRA_ROUTE, SECURITY_ROUTE_PATH);
addPermissionsPoliciesHeader(OTRA_ROUTE, SECURITY_ROUTE_PATH);

echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . OTRA_ROUTE . VERSION . 'che') . '.gz');
exit;
