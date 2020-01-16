<?php
/**
 * Sites routes
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);
namespace config;

class Routes
{
  public static array $default = [
    'pattern' => '/frontend/index',
    'bundle' => 'App',
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'indexAction',
    'route' => 'homePage'
  ],

  $_ = [
    'exception' => [
      'chunks' => ['exception'],
      'core' => true,
      'resources' => [
        'core_css' => ['otraException'],
        'core_js' => ['tools']
      ]
    ],

    'refreshSQLLogs' => [
      'chunks' => ['/dbg/refreshSQLLogs', 'lib', 'otra', 'profiler', 'refreshSQLLogsAction'],
      'core' => true
    ],

    'clearSQLLogs' => [
      'chunks' => ['/dbg/clearSQLLogs', 'lib', 'otra', 'profiler', 'clearSQLLogsAction'],
      'core' => true
    ],
    'profiler' => [
      'chunks' => ['/dbg', 'lib', 'otra', 'profiler', 'indexAction'],
      'core' => true
    ]
  ];

  public static function init()
  {
    self::$_ = array_merge(
      self::$_,
      require BASE_PATH . 'bundles/config/Routes.php'); // TODO find a way to allow the parenthesis to be correctly placed ! For now, change it breaks the production task code :/
  }
}

Routes:: init();
?>
