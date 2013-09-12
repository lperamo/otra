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
			'resource' => array(
				'js' => array('jquery', 'main'),
				'css' => array(CMS_CSS_PATH . 'generic', CMS_CSS_PATH . 'main', CMS_CSS_PATH . 'form')
			)),
		'logout' => array('/logout', 'CMS', 'frontend', 'connection', 'logoutAction'),
		'ajaxShowArticle' => array('/ajaxArticle/show', 'CMS', 'frontend', 'ajaxArticle', 'showAction'),
		'ajaxConnection' => array('/ajaxConnection/ajaxLogin', 'CMS', 'frontend', 'connection', 'ajaxLoginAction'),

		'backendModules' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'),
		'backendGeneral' => array('/backend/general', 'CMS', 'backend', 'index', 'generalAction'),
		'backendStats' => array('/backend/stats', 'CMS', 'backend', 'index', 'statsAction'),
		'backendUsers' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction'),

		'backendAjaxModules' => array('/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction'),
		'backendAjaxGeneral' => array('/backend/ajax/general', 'CMS', 'backend', 'ajaxGeneral', 'indexAction'),
		'backendAjaxStats' => array('/backend/ajax/stats', 'CMS', 'backend', 'ajaxStats', 'indexAction'),
		'backendAjaxUsers' => array('/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction'),
		// keep these routes in last position because it's too generic !!
		'backend' => array('/backend', 'CMS', 'backend', 'index', 'indexAction'),
		'index' => array('/', 'CMS', 'frontend', 'article', 'showAction')
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

		$chunks = array_combine(array('pattern', 'bundle', 'module', 'controller', 'action'), self::$routes['chunks'][$route]);
		$chunks['route'] = $route;
		extract($chunks);

    $controller = 'bundles\\' . $bundle . '\\modules\\' . $module . '\\controllers\\' . $controller . 'Controller';


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
			$route = $route[0];

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
