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
    'otra_exception' => [
      'chunks' => ['exception'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/otraException'],
        'core_js' => ['tools']
      ]
    ],

    'otra_refreshSQLLogs' => [
      'chunks' => ['/dbg/refreshSQLLogs', '', 'otra', 'profiler', 'refreshSQLLogsAction'],
      'core' => true
    ],

    'otra_clearSQLLogs' => [
      'chunks' => ['/dbg/clearSQLLogs', '', 'otra', 'profiler', 'clearSQLLogsAction'],
      'core' => true
    ],
    'otra_profiler' => [
      'chunks' => ['/dbg', '', 'otra', 'profiler', 'indexAction'],
      'core' => true
    ],
    'otra_404' => [
      'chunks' => ['/404', '', 'otra', 'errors', 'error404Action'],
      'core' => true
    ],
    'otra_css' => [
      'chunks' => ['/profiler/css', '', 'otra', 'heavyProfiler', 'cssAction'],
      'core' => true,
      'resources' => [
        'core_css' => ['pages/sassTree/sassTree']
      ]
    ],
    'otra_template_structure' => [
      'chunks' => ['/profiler/templateStructure', '', 'otra', 'heavyProfiler', 'templateStructureAction'],
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
