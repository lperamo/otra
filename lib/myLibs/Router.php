<?
/**
 * THE framework router
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs;

use lib\myLibs\Controller,
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
		if (false === is_array($params))
			$params = [$params];

		// We ensure that our input array really contains 5 parameters in order to make array_combine works
		/**
		 * We extract potentially those variables from $chunks
		 *
		 * @var $action
		 * @var $bundle
		 * @var $controller
		 * @var $module
		 * @var $pattern
		 */
		extract($chunks = array_combine(
			['pattern', 'bundle', 'module', 'controller', 'action'],
			array_pad(Routes::$_[$route]['chunks'], 5, null)
		));

		$action = ('prod' === XMODE)
			? 'cache\\php\\' . $action //'cache\\php\\' . $controller . 'Controller'
			: (true === isset(Routes::$_[$route]['core']) ? '' : 'bundles\\') . $bundle . '\\' . $module . '\\controllers\\' . $controller . '\\'  . $action;

		if (false === $launch)
			return $action;

		$chunks['route'] = $route;
		$chunks['css'] = $chunks['js'] = false;

		// Do we have some resources for this route...
		if (true === isset(Routes::$_[$route]['resources']))
		{
			$resources = Routes::$_[$route]['resources'];
			$chunks['js'] = (
				true === isset($resources['bundle_js'])
				|| true === isset($resources['module_js'])
				|| true === isset($resources['_js'])
			);
			$chunks['css'] = (
				true === isset($resources['bundle_css'])
				|| true === isset($resources['module_css'])
				|| true === isset($resources['_css'])
			);
		}

		new $action($chunks, $params);
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
		foreach (Routes::$_ as $routeName => &$routeData)
		{
			$routeUrl = $routeData['chunks'][0];

			if (0 !== strpos($pattern, $routeUrl))
				continue;

			$params = explode('/', trim(substr($pattern, strlen($routeUrl)), '/'));

			if ('' === $params[0])
				return [$routeName, []];

			// We destroy the parameters after ? because we only want rewritten parameters
			$derParam = count($params) - 1;
			$paramsFinal = explode('?', $params[$derParam]);
			$params[$derParam] = $paramsFinal[0];

			return [$routeName, $params];
		}

		return false;
	}

	/**
	 * @param string $route
	 *
	 * @return string
	 */
	public static function getRouteUrl(string $route) : string { return Routes::$_[$route]['chunks'][0]; }
}
?>
