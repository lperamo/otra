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
    'profiler' => array(
      'chunks' => array('/dbg', 'lib\\myLibs', 'core', 'profiler', 'indexAction'),
      'core' => true
    ),
    'clearSQLLogs' => array(
      'chunks' => array('/dbg/clearSQLLogs', 'lib\\myLibs', 'core', 'profiler', 'clearSQLLogsAction'),
      'core' => true
    ),
    'showArticle' => array(
      'chunks' => array('/article/show', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    ),
    'logout' => array(
      'chunks' => array('/logout', 'CMS', 'frontend', 'connection', 'logoutAction')
    ),
    'ajaxShowArticle' => array(
      'chunks' => array('/ajaxArticle/show', 'CMS', 'frontend', 'ajaxArticle', 'showAction')
    ),
    'ajaxConnection' => array(
      'chunks' => array('/ajaxConnection/ajaxLogin', 'CMS', 'frontend', 'connection', 'ajaxLoginAction')
    ),

    // ---------
    'backendModules' => array(
      'chunks' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'),
      'resources' => array(
        '_js' => array('modules'),
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),

    'moduleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/module', 'CMS', 'backend', 'ajaxModules', 'searchModuleAction')
    ),
    'elementSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/element', 'CMS', 'backend', 'ajaxModules', 'searchElementAction')
    ),
    'articleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/article', 'CMS', 'backend', 'ajaxModules', 'searchArticleAction')
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
      'chunks' => array('/backend/ajax/users/add', 'CMS', 'backend', 'ajaxUsers', 'addAction')
    ),
    'editUser' => array(
      'chunks' => array('/backend/ajax/users/edit', 'CMS', 'backend', 'ajaxUsers', 'editAction')
    ),
    'deleteUser' => array(
      'chunks' => array('/backend/ajax/users/delete', 'CMS', 'backend', 'ajaxUsers', 'deleteAction')
    ),
    'searchUser' => array(
      'chunks' => array('/backend/ajax/users/search', 'CMS', 'backend', 'ajaxUsers', 'searchAction')
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
