<?
/**
 * THE framework router
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    config\Routes;

class Router
{
	/**
	 * Retrieve the controller's path that we want or launches the route !
	 *
	 * @param string 			 $route  The wanted route
	 * @param string|array $params Additional params
	 * @param bool 				 $launch True if we have to launch the route or just retrieve the path (do we really need this ?)
	 *
	 * @return string Controller's path
	 */
	public static function get(string $route = 'index', array $params = [], bool $launch = true)
	{
		if(!is_array($params))
			$params = [$params];

		extract($chunks = array_combine(['pattern', 'bundle', 'module', 'controller', 'action'], Routes::$_[$route]['chunks']));
		$chunks['route'] = $route;
		$chunks['css'] = $chunks['js'] = false;

		if(isset(Routes::$_[$route]['resources']))
		{
			$resources = Routes::$_[$route]['resources'];
			$chunks['js'] = (isset($resources['bundle_js']) || isset($resources['module_js']) || isset($resources['_js']));
			$chunks['css'] = (isset($resources['bundle_css']) || isset($resources['module_css']) || isset($resources['_css']));
		}

    $controller = ('prod' == XMODE)
     	? 'cache\\php\\' . $controller . 'Controller'
	    : (isset(Routes::$_[$route]['core']) ? '' : 'bundles\\') . $bundle . '\\' . $module . '\\controllers\\' . $controller . 'Controller';

		if($launch)
			new $controller($chunks, $params);
		else
			return Routes::$_[$route] . 'Controller'; // TODO
	}

	/**
	 * Check if the pattern is present among the routes
	 *
	 * @param string $pattern The pattern to check
	 *
	 * @return bool|array The route and the parameters if they exist, false otherwise
	 */
	public static function getByPattern(string $pattern)
	{
		foreach(Routes::$_ as $key => $route)
		{
			$route = $route['chunks'][0];

			if(0 === strpos($pattern, $route))
			{
				$params = explode('/', trim(substr($pattern, strlen($route)), '/'));

				if('' == $params[0])
					return [$key, []];

				// We destroy the parameters after ? because we want only rewrited parameters
				$derParam = count($params) - 1;
				$paramsFinal = explode('?', $params[$derParam]);
				$params[$derParam] = $paramsFinal[0];

				return [$key, $params];
			}
		}

		return false;
	}
}
?>
