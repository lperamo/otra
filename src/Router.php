<?php
/**
 * THE framework router
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra;

use otra\config\Routes;
use const otra\cache\php\{APP_ENV, BASE_PATH, CACHE_PATH, CLASSMAP, DIR_SEPARATOR, PROD};

/**
 * @package otra
 */
abstract class Router
{
  private const
    OTRA_ROUTE_PREFIX_KEY = 'prefix',
    OTRA_ROUTE_RESOURCES_KEY = 'resources';

  public const
    OTRA_DEFAULT_CONTENT_TYPE = 'text/html; charset=utf-8',
    OTRA_ROUTE_CHUNKS_KEY = 'chunks',
    OTRA_ROUTE_CONTENT_TYPE_KEY = 'content-type',
    OTRA_ROUTE_METHOD_KEY = 'method',
    OTRA_ROUTE_URL_KEY = 0;

  final public const
    OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME = 0,
    OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS = 1;

  // Mapping between HTTP status codes and X-Robots-Tag headers
  final public const array 
    HTTP_STATUS_ROBOTS_TAGS =
    [
      301 => 'noindex',                      // Moved Permanently
      302 => 'noindex, nofollow',            // Found / Temporary Redirect
      307 => 'noindex, nofollow',            // Temporary Redirect
      308 => 'noindex',                      // Permanent Redirect
      401 => 'noindex, noarchive',           // Unauthorized
      403 => 'noindex, noarchive',           // Forbidden
      404 => 'noindex, follow',              // Not Found
      410 => 'noindex, follow',              // Gone
      451 => 'noindex, noarchive'            // Unavailable for Legal Reasons
    ];

  /**
   * Retrieve the controller's path that we want or launches the route !
   *
   * @param string 			    $route            The wanted route
   * @param string[]|string $params           Additional params
   * @param bool 				    $launch           True if we have to launch the route or just retrieve the path
   * @param bool            $internalRedirect True if we redirect from a controller/action to another one
   *
   * @return string|Controller Controller's path
   */
  public static function get(
    string $route = 'index',
    array|string $params = [],
    bool $launch = true,
    bool $internalRedirect = false
  )
  : string|Controller
  {
    // We ensure that our input array really contains 5 parameters in order to make array_combine works
    [
      'action' => $action,
      'bundle' => $bundle,
      'controller' => $controller,
      'module' => $module
    ] =
      $otraParams = array_combine(
        ['pattern', 'bundle', 'module', 'controller', 'action'],
        array_pad(Routes::$allRoutes[$route][self::OTRA_ROUTE_CHUNKS_KEY], 5, null)
      );

    $finalAction = '';

    if (PROD === $_SERVER[APP_ENV] && 'cli' !== PHP_SAPI)
      $finalAction = 'otra\\cache\\php\\' . $action; //'otra\\cache\\php\\' . $controller . 'Controller'
    else
    {
      if (!isset(Routes::$allRoutes[$route]['core']))
        $finalAction = 'bundles\\' . $bundle . '\\';

      $finalAction .= $module . '\\controllers\\' . $controller . '\\' . ucfirst($action);
    }

    // If the action class does not exist, then it is maybe a Composer module that needs the bundle's name to
    // differentiate it from the installed version of the module. We use `ob_` like functions to avoid printing error
    // when not finding classes
    ob_start();

    if (!array_key_exists($finalAction, CLASSMAP))
      $finalAction = (
        isset(Routes::$allRoutes[$route][self::OTRA_ROUTE_PREFIX_KEY])
          ? Routes::$allRoutes[$route][self::OTRA_ROUTE_PREFIX_KEY] . '\\'
          : ''
        ) . $finalAction;

    ob_end_clean();

    if (!$launch)
      return $finalAction;

    $otraParams['route'] = $route;
    $otraParams['css'] = $otraParams['js'] = false;
    $otraParams['internalRedirect'] = $internalRedirect;

    // Do we have some resources for this route...
    if (isset(Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY]))
    {
      $resources = Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY];
      $otraParams['js'] = (
        isset($resources['bundle_js'])
        || isset($resources['module_js'])
        || isset($resources['_js'])
      );
      $otraParams['css'] = (
        isset($resources['bundle_css'])
        || isset($resources['module_css'])
        || isset($resources['_css'])
      );
    }

    if (!is_array($params))
      $params = [$params];

    /** Preventing redirections from crashing the application */
    if ('cli' !== PHP_SAPI
      && !str_starts_with(
        str_replace(
          ['\\', BASE_PATH],
          [DIR_SEPARATOR, ''],
          debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]['file']
        ),
        'web/index'
      )
      && !$internalRedirect
    )
    {
      // Is it a static page
      if (isset(Routes::$allRoutes[$route][self::OTRA_ROUTE_RESOURCES_KEY]['template']))
      {
        header('Content-Encoding: br');
        echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route . 'v1che') . '.br'); // version to change
        exit;
      }

      // Otherwise, for dynamic pages...
      require_once CACHE_PATH . 'php/' . $route . '.php';
    }

    return new $finalAction($otraParams, $params);
  }

  /**
   * Check if the pattern is present among the routes and launch a 404 error page if it does not
   *
   * @param string $userUrl The pattern to check
   *
   * @throws OtraException
   * @return array{0:string,1:string[]} The route and the parameters if they exist, false otherwise
   */
  public static function getByPattern(string $userUrl) : array
  {
    [$patternFound, $routeName, $firstBracketPosition, $foundParameters, $routeUrl] = self::doesPatternExist($userUrl);

    // We do not have been able to find a matching route
    if (!$patternFound)
    {
      http_response_code(404);
      header('X-Robots-Tag: ' . self::HTTP_STATUS_ROBOTS_TAGS[404]);
      return array_key_exists('404', Routes::$allRoutes) ? ['404', []] : ['otra_404', []];
    } else
    {
      $routeConfig = Routes::$allRoutes[$routeName];
      $statusCode = $routeConfig['httpStatus'][Routes::ROUTES_HTTP_STATUS] ?? null;

      if ($statusCode !== null && $statusCode >= 300 && $statusCode < 452)
      {
        http_response_code($statusCode);

        // Check if the status code requires an X-Robots-Tag header
        if (isset(self::HTTP_STATUS_ROBOTS_TAGS[$statusCode]))
          header('X-Robots-Tag: ' . self::HTTP_STATUS_ROBOTS_TAGS[$statusCode]);

        // Handle 3xx redirections if a URL is provided
        if ($statusCode < 400)
        {
          $redirectUrl = $routeConfig['httpStatus'][Routes::ROUTES_HTTP_REDIRECTION_URL] ?? null;

          if ($redirectUrl !== null)
          {
            header('Location: ' . $redirectUrl);
            throw new OtraException(code: 0, exit: true);
          }
        }
      }
    }

    // The route is found, do we have parameters to get?
    if ($firstBracketPosition === false)
      return [$routeName, []];

    // We have found parameters so let's get them!
    array_shift($foundParameters);
    $params = [];

    // get the parameters names
    preg_match_all('@{([^}]+)}@', $routeUrl, $routeParameters);

    // remove the global result
    array_shift($routeParameters);

    // flatten the parameters array
    $routeParameters = $routeParameters[0];

    // finalizing the parameters array
    foreach($foundParameters as $foundParameterKey => $foundParameter)
    {
      $params[$routeParameters[$foundParameterKey]]= $foundParameter[0];
    }

    return [$routeName, $params];
  }

  /**
   * Check if the pattern is present among the routes.
   *
   * @param string $userUrl The pattern to check
   *
   * @throws OtraException
   * @return array{0:bool,1:string,2:int,3:array,4:string} [$patternFound, $routeName, $firstBracketPosition, $foundParameters, $routeUrl]
   */
  public static function doesPatternExist(string $userUrl) : array
  {
    if (empty(Routes::$allRoutes))
      throw new OtraException('There are currently no routes.');

    $patternFound = false;
    $foundParameters = [];
    $interrogationMarkPosition = mb_strpos($userUrl, '?');
    $userUrlHasGetParameters = $interrogationMarkPosition !== false;

    if ($userUrlHasGetParameters)
      $userUrlWithoutGetParameters = substr($userUrl, 0, $interrogationMarkPosition);

    /** @var string $routeName */
    foreach (Routes::$allRoutes as $routeName => $routeData)
    {
      if (!in_array(
        $_SERVER['REQUEST_METHOD'],
        $routeData[self::OTRA_ROUTE_METHOD_KEY] ?? ['GET']
      ))
        continue;

      if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS')
      {
        if ($_SERVER['CONTENT_TYPE'] === '')
          $_SERVER['CONTENT_TYPE'] = self::OTRA_DEFAULT_CONTENT_TYPE;

        // We use `str_contains` to not be forced to use regexp for multipart/form-data boundaries for example
        if (!str_contains(
          $_SERVER['CONTENT_TYPE'],
          $routeData[self::OTRA_ROUTE_CONTENT_TYPE_KEY] ?? self::OTRA_DEFAULT_CONTENT_TYPE)
        )
          continue;
      } elseif (!in_array(
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'],
        $routeData[self::OTRA_ROUTE_METHOD_KEY] ?? ['GET']
      ))
        continue;

      /** @var string $routeUrl */
      $routeUrl = $routeData[self::OTRA_ROUTE_CHUNKS_KEY][self::OTRA_ROUTE_URL_KEY];
      $firstBracketPosition = mb_strpos($routeUrl, '{');

      // If the route from the configuration does not contain parameters
      if ($firstBracketPosition === false)
      {
        // Is it the route we are looking for? It is the case if:
        // 1. The route from the configuration is included in the user url AND
        // 2. the user url does not have GET parameters and is equal to the route OR
        //    the user url does have GET parameters and the portion without GET parameters is equal to the route
        // AND does this user url NOT contain parameters like the route
        if (str_contains($userUrl, $routeUrl)
          && (!$userUrlHasGetParameters && $routeUrl === $userUrl
            || $userUrlHasGetParameters && $userUrlWithoutGetParameters === $routeUrl)
        )
        {
          $patternFound = true;
          break;
        } else
          continue;
      }

      $firstPartUntilParameters = substr($routeUrl, 0, $firstBracketPosition);

      // This is maybe the route (with parameters) we are looking for!
      if (str_contains($userUrl, $firstPartUntilParameters))
      {
        $routeRegexp = '@^' . preg_replace('@{[^}]*}@', '([^/?]*)', $routeUrl) .
          '(?:\?(?:[a-zA-Z]+=\w+)(?:&?(?:[a-zA-Z]+=\w+))*)?$@';

        // The beginning of the route is ok, is the parameters' section ok too?
        if (preg_match($routeRegexp, $userUrl, $foundParameters, PREG_OFFSET_CAPTURE))
        {
          $patternFound = true;
          break;
        }
      }
    }

    // We do not have been able to find a matching route
    if ($patternFound)
      return [true, $routeName, $firstBracketPosition, $foundParameters, $routeUrl];
    else
      return [false, null, null, null, null];
  }

  /**
   *
   * @return string
   */
  public static function getRouteUrl(string $route, array $params = []) : string
  {
    $paramsString = '';

    foreach($params as $value)
    {
      $paramsString .= DIR_SEPARATOR . $value;
    }

    return Routes::$allRoutes[$route][self::OTRA_ROUTE_CHUNKS_KEY][Routes::ROUTES_CHUNKS_URL] . $paramsString;
  }

  /**
   * @return string Long route name without parameters with no ending slash
   */
  public static function getRouteUrlWithoutParameters(string $routeName) : string
  {
    $routeLongName = Routes::$allRoutes[$routeName][self::OTRA_ROUTE_CHUNKS_KEY][Routes::ROUTES_CHUNKS_URL];
    $bracketPosition = strpos($routeLongName, '{');

    return $bracketPosition !== false
      ? mb_substr($routeLongName, 0, $bracketPosition - 1)
      : $routeLongName;
  }
}
