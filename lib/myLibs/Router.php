<?
/**
 * THE framework router
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs;

use config\Routes;

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
    // We ensure that our input array really contains 5 parameters in order to make array_combine works
    /**
     * We extract potentially those variables from $baseParams
     *
     * @var $action
     * @var $bundle
     * @var $controller
     * @var $module
     * @var $pattern
     */
    extract($baseParams = array_combine(
      ['pattern', 'bundle', 'module', 'controller', 'action'],
      array_pad(Routes::$_[$route]['chunks'], 5, null)
    ));

    $action = ('prod' === $_SERVER['APP_ENV'] && 'cli' !== php_sapi_name())
      ? 'cache\\php\\' . $action //'cache\\php\\' . $controller . 'Controller'
      : (true === isset(Routes::$_[$route]['core'])
        ? ''
        : 'bundles\\') . $bundle . '\\' . $module . '\\controllers\\' . $controller . '\\'  . ucfirst($action);

    if (false === $launch)
      return $action;

    $baseParams['route'] = $route;
    $baseParams['css'] = $baseParams['js'] = false;

    // Do we have some resources for this route...
    if (true === isset(Routes::$_[$route]['resources']))
    {
      $resources = Routes::$_[$route]['resources'];
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

    if (false === is_array($params))
      $params = [$params];

    /** Preventing redirections from crashing the application (the caller function is more or less far depending on
     * the development mode ($_SERVER['APP_ENV']) so we have :
     * debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[0] for dev mode
     * debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1] for prod mode
     */
    if ('cli' !== PHP_SAPI
      && str_replace(
        ['\\', BASE_PATH],
        ['/', ''],
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[(int) !($_SERVER['APP_ENV'] === 'prod')]['file']
      ) !== 'web/Resources.php')
    {
      // Is it a static page
      if (true === isset(Routes::$_[$route]['resources']['template']))
      {
        header('Content-Encoding: gzip');
        echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route . 'v1che') . '.gz'); // version to change
        exit;
      }

      // Otherwise for dynamic pages...
      require_once CACHE_PATH . 'php/' . $route . '.php';
    }

    new $action($baseParams, $params);
  }

  /**
   * Check if the pattern is present among the routes. TODO fix the named parameters system
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
      $mainPattern = $routeData['mainPattern'] ?? $routeUrl;

      // This is not the route you are looking for !  false === isset($routeData['mainPattern']) &&
      if (false === strpos($pattern, $mainPattern))
        continue;

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
      if (true === isset($routeData['mainPattern']))
      {
        $parametersName = explode('/', substr($routeUrl, strlen($mainPattern)));

        // We check the number of parameters ...
        if (count($parametersName) !== count($params))
        {
          echo 'The number of parameters doesn\'t match !';
          exit(1);
        }

        // ...and we name the passed parameters accordingly to the route configuration
        $newParams = [];

        foreach($params as $key => $param)
        {
          $newParams[substr($parametersName[$key], 1, strlen($parametersName[$key]) - 2)] = $param;
        }
      } else
        $newParams = $params;

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
