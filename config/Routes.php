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
        'cmsJs' => array('jquery', 'main', 'connection'),
        'cmsCss' => array('header', 'footer', 'generic', 'main', 'form')
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
        'cmsJs' => array('jquery', 'backend'),
        'cmsCss' => array('generic', 'interface', 'form')
      )
    ),
    'backendGeneral' => array(
      'chunks' => array('/backend/general', 'CMS', 'backend', 'index', 'generalAction'),
      'resources' => array(
        'cmsJs' => array('jquery', 'backend'),
        'cmsCss' => array('generic', 'interface', 'form')
      )
    ),
    'backendStats' => array(
      'chunks' => array('/backend/stats', 'CMS', 'backend', 'index', 'statsAction'),
      'resources' => array(
        'cmsJs' => array('jquery','backend'),
        'cmsCss' => array('generic', 'interface', 'form')
      )
    ),
    'backendUsers' => array(
      'chunks' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction'),
      'resources' => array(
        'cmsJs' => array('jquery', 'backend', 'form'),
        'cmsCss' => array('generic', 'interface', 'form')
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
      'chunks' => array('/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction')
    ),
    // keep these routes in last position because it's too generic !!
    'backend' => array(
      'chunks' => array('/backend', 'CMS', 'backend', 'index', 'indexAction'),
      'resources' => array(
        'cmsJs' => array('jquery', 'main'),
        'cmsCss' => array('header', 'footer', 'generic', 'main', 'form')
      )
    ),
    'index' => array(
      'chunks' => array('/', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'cmsJs' => array('jquery', 'main', 'connection'),
        'cmsCss' => array('header', 'footer', 'generic', 'main', 'form')
      )
    )
  );
}
?>
