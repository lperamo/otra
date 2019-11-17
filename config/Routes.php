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
  public static $default = [
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
        'core_css' => ['OtraException'],
        'core_js' => ['tools']
      ]
    ],

    'refreshSQLLogs' => [
      'chunks' => ['/dbg/refreshSQLLogs', 'lib', 'myLibs', 'profiler', 'refreshSQLLogsAction'],
      'core' => true
    ],

    'clearSQLLogs' => [
      'chunks' => ['/dbg/clearSQLLogs', 'lib', 'myLibs', 'profiler', 'clearSQLLogsAction'],
      'core' => true
    ],
    'profiler' => [
      'chunks' => ['/dbg', 'lib', 'myLibs', 'profiler', 'indexAction'],
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
