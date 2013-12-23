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

    'backendModules' => array(
      'chunks' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'),
      'resources' => array(
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),
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
    'backendUsers' => array(
      'chunks' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction'),
      'resources' => array(
        '_js' => array('users'),
        'bundle_js' => array('jquery', 'backend', 'form'),
        'bundle_css' => array('generic', 'interface', 'form')
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
    'backendAjaxUsers' => array(
      'chunks' => array('/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction'),
      'resources' => array(
        'js' => array('users'),
        'bundle_css' => array('users')
      )
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
