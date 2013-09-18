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
        'firstJs' => array('http://code.jquery.com/jquery-1.10.1.min'),
        'js' => array(),
        'cmsJs' => array('main', 'connection'),
        'css' => array(),
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
      'chunks' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction')
    ),
    'backendGeneral' => array(
      'chunks' => array('/backend/general', 'CMS', 'backend', 'index', 'generalAction')
    ),
    'backendStats' => array(
      'chunks' => array('/backend/stats', 'CMS', 'backend', 'index', 'statsAction')
    ),
    'backendUsers' => array(
      'chunks' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction')
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
      'chunks' => array('/backend', 'CMS', 'backend', 'index', 'indexAction')
    ),
    'index' => array(
      'chunks' => array('/', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'firstJs' => array('http://code.jquery.com/jquery-1.10.1.min'),
        'js' => array(),
        'cmsJs' => array('main', 'connection'),
        'css' => array(),
        'cmsCss' => array('header', 'footer', 'generic', 'main', 'form')
      )
    )
  );
}
?>
