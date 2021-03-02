<?php
/**
 * Sites routes
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);
namespace config;

/**
 * @package config
 */
abstract class Routes
{
  public static array $default = [
    'pattern' => '/frontend/index',
    'bundle' => 'App',
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'indexAction',
    'route' => 'homePage'
  ],

  $allRoutes = [
    'otra_exception' => [
      'chunks' => ['exception'],
      'core' => true,
      'resources' => [
        'core_css' => ['otraException'],
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
    ]
  ];

  public static function init()
  {
    self::$allRoutes = array_merge(
      self::$allRoutes,
      require BASE_PATH . 'bundles/config/Routes.php'); // TODO find a way to allow the parenthesis to be correctly placed ! For now, change it breaks the production task code :/
  }
}

Routes:: init();

