<?
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
    'bundle' => 'CMS',
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'indexAction',
    'route' => 'showArticle'
  ],

  $_ = [
    'exception' => [
      'chunks' => ['exception'],
      'core' => true,
      'resources' => [
        'core_css' => ['LionelException'],
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
      require BASE_PATH . 'bundles/config/Routes.php'); // TODO find a way to allow the parenthese to be correctly placed ! For now, change it breaks the production task code :/
  }
}

Routes:: init();
?>
