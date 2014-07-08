<?
namespace config;

class Routes
{
  public static $default = array(
    'pattern' => '/frontend/index',
    'bundle' => 'CMS',
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'indexAction',
    'route' => 'showArticle'
  ),

  $_ = array(
    'refreshSQLLogs' => array(
      'chunks' => array('/dbg/refreshSQLLogs', 'lib\\myLibs', 'core', 'profiler', 'refreshSQLLogsAction'),
      'core' => true
    ),

    'clearSQLLogs' => array(
      'chunks' => array('/dbg/clearSQLLogs', 'lib\\myLibs', 'core', 'profiler', 'clearSQLLogsAction'),
      'core' => true
    ),
    'profiler' => array(
      'chunks' => array('/dbg', 'lib\\myLibs', 'core', 'profiler', 'indexAction'),
      'core' => true
    ),

    'showArticle' => array(
      'chunks' => array('/article/show', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      ),
    ),
    'logout' => array(
      'chunks' => array('/logout', 'CMS', 'frontend', 'connection', 'logoutAction')
    ),
    'ajaxShowArticle' => array(
      'chunks' => array('/ajaxArticle/show', 'CMS', 'frontend', 'ajaxArticle', 'showAction'),
      'bootstrap' => array('article2')
    ),
    'ajaxConnection' => array(
      'chunks' => array('/ajaxConnection/ajaxLogin', 'CMS', 'frontend', 'connection', 'ajaxLoginAction'),
      'post' => array('pwd' => ' ', 'email' => ' ')
    ),

    // ---------
    'backendModules' => array(
      'chunks' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'),
      'resources' => array(
        '_js' => array('modules'),
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      ),
      'session' => array('sid' => '1')
    ),

    'moduleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/module', 'CMS', 'backend', 'ajaxModules', 'searchModuleAction'),
      'get' => array('search' => '')
    ),
    'elementSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/element', 'CMS', 'backend', 'ajaxModules', 'searchElementAction'),
      'get' => array('search' => '')
    ),
    'articleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/article', 'CMS', 'backend', 'ajaxModules', 'searchArticleAction'),
      'get' => array('search' => '')
    ),
    'getElements' => array(
      'chunks' => array('/backend/ajax/modules/get/elements', 'CMS', 'backend', 'ajaxModules', 'getElementsAction')
    ),
    'backendAjaxModules' => array(
      'chunks' => array('/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction')
    ),

    // -----------

    'backendGeneral' => array(
      'chunks' => array('/backend/general', 'CMS', 'backend', 'index', 'generalAction'),
      'resources' => array(
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),
    'backendStats' => array(
      'chunks' => array('/backend/stats', 'CMS', 'backend', 'index', 'statsAction'),
      'resources' => array(
        'bundle_js' => array('jquery','backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),

    // --------------
    'backendUsers' => array(
      'chunks' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction'),
      'resources' => array(
        '_js' => array('users'),
        'bundle_js' => array('jquery', 'backend', 'form', 'notifications'),
        'bundle_css' => array('generic', 'interface', 'form', 'notifications')
      )
    ),
    'addUser' => array(
      'chunks' => array('/backend/ajax/users/add', 'CMS', 'backend', 'ajaxUsers', 'addAction'),
      'post' => array('mail' => ' ', 'pwd' => ' ', 'pseudo' => ' ', 'role' => ' '),
      'session' => array('sid' => array('role' => 1))
    ),
    'editUser' => array(
      'chunks' => array('/backend/ajax/users/edit', 'CMS', 'backend', 'ajaxUsers', 'editAction'),
      'post' => array('id_user' => 0, 'mail' => ' ', 'pwd' => ' ', 'pseudo' => ' ', 'role' => ' ', 'oldMail' => ' '),
      'session' => array('sid' => array('role' => 1))
    ),
    'deleteUser' => array(
      'chunks' => array('/backend/ajax/users/delete', 'CMS', 'backend', 'ajaxUsers', 'deleteAction'),
      'post' => array('id_user' => 0),
      'session' => array('sid' => array('role' => 1))
    ),
    'searchUser' => array(
      'chunks' => array('/backend/ajax/users/search', 'CMS', 'backend', 'ajaxUsers', 'searchAction'),
      'post' => array('type' => ' ', 'mail' => ' ', 'pseudo' => ' ', 'role' => ' ', 'limit' => 0, 'prev' => 0, 'last' => 1),
      'session' => array('sid' => array('role' => 1))
    ),
    'backendAjaxUsers' => array(
      'chunks' => array('/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction'),
      'resources' => array(
        '_js' => array('users'),
        'bundle_css' => array('users')
      )
    ),

    'backendAjaxModules' => array(
      'chunks' => array('/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction')
    ),
    'backendAjaxGeneral' => array(
      'chunks' => array('/backend/ajax/general', 'CMS', 'backend', 'ajaxGeneral', 'indexAction')
    ),
    'backendAjaxStats' => array(
      'chunks' => array('/backend/ajax/stats', 'CMS', 'backend', 'ajaxStats', 'indexAction')
    ),


    // keep these routes in last position because it's too generic !!
    'backend' => array(
      'chunks' => array('/backend', 'CMS', 'backend', 'index', 'indexAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    ),
    'index' => array(
      'chunks' => array('/', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    )
  );
}
?>
