<?php
/**
 * THE framework router
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra;

use config\Routes;

/**
 * @package otra
 */
abstract class Router
{
  private const OTRA_ROUTE_CHUNKS_KEY = 'chunks',
    OTRA_ROUTE_RESOURCES_KEY = 'resources';

  public const
    OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME = 0,
    OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS = 1;

  /**
   * Retrieve the controller's path that we want or launches the route !
   *
   * @param string 			 $route  The wanted route
   * @param array|string $params Additional params
   * @param bool 				 $launch True if we have to launch the route or just retrieve the path (do we really need this ?)
   *
   * @return string|Controller Controller's path
   */
  public static function get(string $route = 'index', array|string $params = [], bool $launch = true) : string|Controller
  {
    // We ensure that our input array really contains 5 parameters in order to make array_combine works
    [
      'action' => $action,
      'bundle' => $bundle,
      'controller' => $controller,
      'module' => $module
    ] =
      $baseParams = array_combine(
      ['pattern', 'bundle', 'module', 'controller', 'action'],
      array_pad(Routes::$allRoutes[$route][self::OTRA_ROUTE_CHUNKS_KEY], 5, null)
    );

    $finalAction = '';

    if (PROD === $_SERVER[APP_ENV] && 'cli' !== PHP_SAPI)
      $finalAction = 'cache\\php\\' . $action; //'cache\\php\\' . $controller . 'Controller'
    else
    {
      if (!isset(Routes::$allRoutes[$route]['core']))
        $finalAction = 'bundles\\';

      $finalAction .= $bundle . '\\' . $module . '\\controllers\\' . $controller . '\\' . ucfirst($action);
    }

    if (!$launch)
      return $finalAction;

    $baseParams['route'] = $route;
    $baseParams['css'] = $baseParams['js'] = false;

    // Do we have some resources for this route...
    if (isset(Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY]))
    {
      $resources = Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY];
      $baseParams['js'] = (
        isset($resources['bundle_js'])
        || isset($resources['module_js'])
        || isset($resources['_js'])
      );
      $baseParams['css'] = (
        isset($resources['bundle_css'])
        || isset($resources['module_css'])
        || isset($resources['_css'])
      );
    }

    if (!is_array($params))
      $params = [$params];

    /** Preventing redirections from crashing the application */
    if ('cli' !== PHP_SAPI
      && substr(str_replace(
        ['\\', BASE_PATH],
        ['/', ''],
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[0]['file']
      ), 0, 9) !== 'web/index')
    {
      // Is it a static page
      if (isset(Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY]['template']))
      {
        header('Content-Encoding: gzip');
        echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route . 'v1che') . '.gz'); // version to change
        exit;
      }

      // Otherwise for dynamic pages...
      require_once CACHE_PATH . 'php/' . $route . '.php';
    }

    return new $finalAction($baseParams, $params);
  }

  /**
   * Check if the pattern is present among the routes. TODO fix the named parameters system
   *
   * @param string $pattern          The pattern to check
   *
   * @return array{0:string,1:array} The route and the parameters if they exist, false otherwise
   *
   * @throws OtraException
   */
  public static function getByPattern(string $pattern) : array
  {
    if (empty(Routes::$allRoutes))
      throw new OtraException('There are currently no routes.');

    $patternFound = false;

    /**
     * @var string $mainPattern
     * @var string $routeName
     * @var string $routeUrl
     */

    foreach (Routes::$allRoutes as $routeName => $routeData)
    {
      $routeUrl = $routeData[self::OTRA_ROUTE_CHUNKS_KEY][0];
      $mainPattern = $routeData['mainPattern'] ?? $routeUrl;

      // This is the route we are looking for !
      if (str_contains($pattern, $mainPattern))
      {
        $patternFound = true;
        break;
      }
    }

    if (!$patternFound)
    {
      header('HTTP/1.0 404 Not Found');

      return in_array('404', array_keys(Routes::$allRoutes)) ? ['404', []] : ['otra_404', []];
    }

    $params = explode('/', trim(substr($pattern, strlen($mainPattern)), '/'));

    // Zero parameters => we have all we need.
    if ('' === $params[0])
      return [$routeName, []];

    // We destroy the parameters after '?' because we only want rewritten parameters
    $derParam = count($params) - 1;
    $paramsFinal = explode('?', $params[$derParam]);
    $params[$derParam] = $paramsFinal[0];

    // Zero parameters once we remove the parameters after '?' ? => we have all we need.
    if ('' === $params[0])
      return [$routeName, []];

    // If there are named parameters in the route configuration
    if (isset($routeData['mainPattern']))
    {
      $parametersName = explode('/', substr($routeUrl, strlen($mainPattern)));

      // We check the number of parameters ...
      if (count($parametersName) !== count($params))
      {
        echo 'The number of parameters does not match !';
        throw new OtraException('', 1, '', NULL, [], true);
      }

      // ...and we name the passed parameters accordingly to the route configuration
      $newParams = [];

      foreach ($params as $key => $param)
      {
        $newParams[substr($parametersName[$key], 1, strlen($parametersName[$key]) - 2)] = $param;
      }
    } else
      $newParams = $params;

    /** TODO why the $newParams variable is not used ??? */

    return [$routeName, $params];
  }

  /**
   * @param string $route
   * @param array  $params
   *
   * @return string
   */
  public static function getRouteUrl(string $route, array $params = []) : string {

    $paramsString = '';

    foreach($params as $value)
    {
      $paramsString .= '/' . $value;
    }

    return Routes::$allRoutes[$route][self::OTRA_ROUTE_CHUNKS_KEY][0] . $paramsString;
  }
}

