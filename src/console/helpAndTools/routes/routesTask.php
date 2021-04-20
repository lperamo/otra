<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

use config\Routes;
use otra\OtraException;

if (!file_exists(BASE_PATH . 'bundles/config/Routes.php'))
{
  echo CLI_WARNING, 'No custom routes are defined.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}
/** Task that show all or one of the routes available for the application.
 * It shows for each related route :
 * - the url
 * - the action
 * - the resources generated
 * - the key used for the cached file names
 */

/**
 * Show [RESOURCE_NAME] in green if the resource file exists, in red otherwise.
 *
 * @param string $resourceExtension
 * @param string $resourceType
 * @param string $basePath
 * @param string $shaName
 * @param string $altColor
 */
function showResourceState(
  string $resourceExtension,
  string $resourceType,
  string $basePath,
  string $shaName,
  string $altColor
) : void
{
  echo (file_exists($basePath . $resourceExtension . '/' . $shaName. '.gz'))
    ? CLI_SUCCESS
    : CLI_ERROR,
    '[', $resourceType, ']', $altColor;
}

/**
 * Show [PHP] in green if the PHP file exists, in red otherwise.
 *
 * @param string $basePath
 * @param string $route
 * @param string $altColor
 */
function showPHPState(string $basePath, string $route, string $altColor) : void
{
  $routePath = $basePath . 'php/';

  if (str_contains($route, 'otra_'))
    $routePath .= 'otraRoutes/';

  $routePath .= $route . '.php';
  echo (file_exists($routePath))
    ? CLI_SUCCESS
    : CLI_ERROR,
    '[PHP]', $altColor;
}

require BASE_PATH . 'config/AllConfig.php';
const ROUTES_ARG_ROUTE = 2,
  ROUTES_CHUNKS_URL = 0;

// 'require_once' needed instead of 'require', if we execute TasksManager::execute multiple times as in tests or some
// scripts
if (!defined('ROUTES_CHUNKS_BUNDLE'))
{
  define('ROUTES_CHUNKS_BUNDLE', 1);
  define('ROUTES_CHUNKS_MODULE', 2);
  define('WIDTH_LEFT', 25);
  define('WIDTH_MIDDLE', 10);
  // The longest text : [PHP] No other resources. [strlen(sha1('ca' . 'route' . config\AllConfig::$version . 'che'))]
  define('WIDTH_RIGHT', 70);
}

const
  ROUTES_CHUNKS_CONTROLLER = 3,
  ROUTES_CHUNKS_ACTION = 4;

$indexLines = 0;

// Check if we want one or all the routes
if (isset($argv[ROUTES_ARG_ROUTE]))
{
  $route = $argv[ROUTES_ARG_ROUTE];

  // If the route does not exist
  if (!isset(Routes::$allRoutes[$route]))
  {
    // We try to find a route which the name is similar
    require CONSOLE_PATH . 'tools.php';
    [$newRoute] = guessWords($route, array_keys(Routes::$allRoutes));

    // And asks the user whether we find what he wanted or not
    $choice = promptUser('There are no route with the name ' . CLI_BASE . $route . CLI_WARNING
      . ' ! Do you mean ' . CLI_BASE . $newRoute . CLI_WARNING . ' ? (y/n)');

    // If our guess is wrong, we apologise and exit !
    if ('n' === $choice)
    {
      echo CLI_ERROR, 'Sorry then !', END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', NULL, [], true);
    }

    $route = $newRoute;
  }

  $routes = [$route => Routes::$allRoutes[$route]];
} else
  $routes = Routes::$allRoutes;

/**
 * @var string  $route
 * @var array   $details
 */
foreach($routes as $route => $details)
{
  if ('otra_exception' === $route )
    continue;

  // Routes and paths management
  $chunks = $details['chunks'];
  $altColor = ($indexLines % 2) ? CLI_INFO : CLI_INFO_HIGHLIGHT;
  echo $altColor, sprintf('%-' . (string)WIDTH_LEFT . 's', $route),
    str_pad('Url', WIDTH_MIDDLE), ': ' , $chunks[ROUTES_CHUNKS_URL], PHP_EOL;

  echo str_pad(' ', WIDTH_LEFT),
    str_pad('Path', WIDTH_MIDDLE),
    ': ' . $chunks[ROUTES_CHUNKS_BUNDLE] . '/' . $chunks[ROUTES_CHUNKS_MODULE] . '/' .
    $chunks[ROUTES_CHUNKS_CONTROLLER] . 'Controller/' . $chunks[ROUTES_CHUNKS_ACTION],
    PHP_EOL;

  // shaName is the encrypted key that match a particular route / version
  $shaName = sha1('ca' . $route . VERSION . 'che');
  $basePath = BASE_PATH . 'cache/';

  echo str_pad(' ', WIDTH_LEFT), 'Resources : ';

  // Resources management : show the state of each resource. Red => missing, green => exists
  if (isset($details['resources']))
  {
    $resources = $details['resources'];

    if (!isset($resources['template']))
      showPHPState($basePath, $route, $altColor);

    if (isset($resources['_css']) || isset($resources['bundle_css']) || isset($resources['module_css']))
    {
      showResourceState('css', 'SCREEN CSS', $basePath, $shaName, $altColor);

      showResourceState(
        'css',
        'PRINT CSS',
        $basePath,
        'print_' . $shaName,
        $altColor
      );
    }

    if (isset($resources['_js'])
      || isset($resources['bundle_js'])
      || isset($resources['module_js'])
      || isset($resources['first_js']))
      showResourceState('js', 'JS', $basePath, $shaName, $altColor);

    if (isset($resources['template']))
      showResourceState('tpl', 'TEMPLATE', $basePath, $shaName, $altColor);
  } else
  {
    showPHPState($basePath, $route, $altColor);
    echo ' No other resources. ';
  }

  echo '[', $shaName, ']', PHP_EOL, END_COLOR;

  // We only show a decoration line if it's not the last route
  end($routes);

  if ($route !== key($routes))
    echo str_repeat('-', WIDTH_LEFT + WIDTH_MIDDLE + WIDTH_RIGHT), PHP_EOL;

  ++$indexLines;
}


