<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\routes;

use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\config\VERSION;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\console\{guessWords,promptUser};

if (!file_exists(BUNDLES_PATH . 'config/Routes.php'))
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
  echo (file_exists($basePath . $resourceExtension . DIR_SEPARATOR . $shaName. '.gz'))
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

// beware require_once only needed for automated tests
require_once BASE_PATH . 'config/AllConfig.php';
const ROUTES_ARG_ROUTE = 2;

// 'require_once' needed instead of 'require', if we execute TasksManager::execute multiple times as in tests or some
// scripts
if (!defined(__NAMESPACE__ . '\\WIDTH_LEFT'))
{
  define(__NAMESPACE__ . '\\WIDTH_LEFT', 25);
  define(__NAMESPACE__ . '\\WIDTH_MIDDLE', 10);
  // The longest text : [PHP] No other resources. [strlen(sha1('ca' . 'route' . config\AllConfig::$version . 'che'))]
  define(__NAMESPACE__ . '\\WIDTH_RIGHT', 70);
}

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

/** @var array<string,array<string, array<int|string,string|array>>> $routes */
foreach($routes as $route => $details)
{
  if ('otra_exception' === $route )
    continue;

  // Routes and paths management
  $chunks = $details['chunks'];
  $altColor = ($indexLines % 2) ? CLI_INFO : CLI_INFO_HIGHLIGHT;
  echo $altColor, sprintf('%-' . WIDTH_LEFT . 's', $route),
    str_pad('Url', WIDTH_MIDDLE), ': ' , $chunks[Routes::ROUTES_CHUNKS_URL], PHP_EOL;

  echo str_pad(' ', WIDTH_LEFT),
    str_pad('Path', WIDTH_MIDDLE),
    ': ' . $chunks[Routes::ROUTES_CHUNKS_BUNDLE] . DIR_SEPARATOR . $chunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR .
    $chunks[Routes::ROUTES_CHUNKS_CONTROLLER] . 'Controller/' . $chunks[Routes::ROUTES_CHUNKS_ACTION],
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


