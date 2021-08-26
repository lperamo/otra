<?php
/**
 * Sites routes
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);
namespace otra\config;

use const otra\cache\php\BUNDLES_PATH;

/**
 * @package otra\config
 */
abstract class Routes
{
  public const
    ROUTES_CHUNKS_URL = 0,
    ROUTES_CHUNKS_BUNDLE = 1,
    ROUTES_CHUNKS_MODULE = 2,
    ROUTES_CHUNKS_CONTROLLER = 3,
    ROUTES_CHUNKS_ACTION = 4;

  public static array $allRoutes = [
    'otra_404' => [
      'chunks' => ['/404', '', 'otra', 'errors', 'error404Action'],
      'core' => true
    ],
    'otra_clearSQLLogs' => [
      'chunks' => ['/dbg/clearSQLLogs', '', 'otra', 'profiler', 'clearSQLLogsAction'],
      'core' => true
    ],
    'otra_css' => [
      'chunks' => ['/profiler/css', '', 'otra', 'profiler', 'cssAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/sassTree/sassTree']
      ]
    ],
    'otra_exception' => [
      'chunks' => ['exception'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/otraException'],
        'core_js' => ['tools']
      ]
    ],
    'otra_logs' => [
      'chunks' => ['/profiler/logs', '', 'otra', 'profiler', 'logsAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/logs/logs']
      ]
    ],
    'otra_refreshSQLLogs' => [
      'chunks' => ['/dbg/refreshSQLLogs', '', 'otra', 'profiler', 'refreshSQLLogsAction'],
      'core' => true
    ],
    'otra_requests' => [
      'chunks' => ['/profiler/requests', '', 'otra', 'profiler', 'requestsAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/requests/requests']
      ]
    ],
    'otra_routes' => [
      'core' => true,
      'chunks' => ['/profiler/routes', '', 'otra', 'profiler', 'routesAction'],
      'resources' => [
        'core_css' => ['pages/routes/routes']
      ]
    ],
    'otra_sql' => [
      'chunks' => ['/profiler/sql', '', 'otra', 'profiler', 'sqlAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/sql/sql'],
        'core_js' => ['profiler']
      ]
    ],
    'otra_templateStructure' => [
      'chunks' => ['/profiler/templateStructure', '', 'otra', 'profiler', 'templateStructureAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/templateStructure/templateStructure']
      ]
    ]
  ];

  public static function init() : void
  {
    self::$allRoutes = array_merge(
      self::$allRoutes,
      require BUNDLES_PATH . 'config/Routes.php'
    );
  }
}

Routes::init();
