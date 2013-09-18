<?
/** THE framework router
 *
 * @author Lionel Péramo */
namespace config;

use lib\myLibs\core\Controller;

class Router
{
	public static $routes = array(
		'showArticle' => array(
			'chunks' => array('/article/show', 'CMS', 'frontend', 'article', 'showAction'),
			'resources' => array(
				'js' => array(),
				'cmsJs' => array('jquery', 'main', 'connection'),
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
        'js' => array(),
        'cmsJs' => array('jquery', 'main', 'connection'),
        'css' => array(),
        'cmsCss' => array('header', 'footer', 'generic', 'main', 'form')
      )
		)
	);

	public static $defaultRoute = array(
		'pattern' => '/frontend/index',
		'bundle' => 'CMS',
	    'module' => 'frontend',
	    'controller' => 'index',
	    'action' => 'indexAction',
	    'route' => 'showArticle'
	  );

	/** Retrieve the controller's path that we want or launches the route !
	 *
	 * @param string 			 $route  The wanted route
	 * @param string|array $params Additional params
	 * @param bool 				 $launch True if we have to launch the route or just retrieve the path (do we really need this ?)
	 *
	 * @return string Controller's path
	 */
	public static function get($route = 'index', $params = array(), $launch = true)
	{
		if(!is_array($params))
			$params = array($params);

		//dump(All_Config::$CMS_CSS_PATH);die;

		$chunks = array_combine(array('pattern', 'bundle', 'module', 'controller', 'action'), self::$routes[$route]['chunks']);
		$chunks['route'] = $route;
		extract($chunks);

    $controller = 'bundles\\' . $bundle . '\\modules\\' . $module . '\\controllers\\' . $controller . 'Controller';

    // var_dump($controller);die;
		if($launch)
			new $controller($chunks, $params);
		else
			return self::$routes[$route] . 'Controller'; // not finished ...yet
	}

	/** Check if the pattern is present among the routes
	 *
	 * @param string $pattern The pattern to check
	 *
	 * @return bool|array The route and the parameters if they exist, false otherwise
	 */
	public static function getByPattern($pattern)
	{
		foreach(self::$routes as $key => $route)
		{
			$route = $route['chunks'][0];

			if(0 === strpos($pattern, $route))
			{
				$params = explode('/', trim(substr($pattern, strlen($route)), '/'));

				if('' == $params[0])
					return array($key, array());

				// We destroy the parameters after ? because we want only rewrited parameters
				$derParam = count($params) - 1;
				$paramsFinal = explode('?', $params[$derParam]);
				$params[$derParam] = $paramsFinal[0];

				return array($key, $params);
			}
		}

		return false;
	}
}
?>
