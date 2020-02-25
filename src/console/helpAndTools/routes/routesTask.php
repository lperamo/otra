<?php

if (file_exists(BASE_PATH . 'bundles/config/Routes.php') === false)
{
  echo CLI_YELLOW, 'No custom routes are defined.', END_COLOR, PHP_EOL;
  throw new \src\OtraException('', 1, '', NULL, [], true);
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
function showResourceState(string $resourceExtension, string $resourceType, string &$basePath, string &$shaName, string &$altColor)
{
  echo (file_exists($basePath . $resourceExtension . '/' . $shaName. '.gz')) ? CLI_LIGHT_GREEN : CLI_LIGHT_RED, '[',
  $resourceType, ']', $altColor;
}

/**
 * Show [PHP] in green if the PHP file exists, in red otherwise.
 *
 * @param string $basePath
 * @param string $route
 * @param string $altColor
 */
function showPHPState(string &$basePath, string &$route, string &$altColor)
{
  echo (file_exists($basePath . 'php' . '/' . $route. '.php') === true) ? CLI_LIGHT_GREEN : CLI_LIGHT_RED, '[PHP]' . $altColor;
}

$alt = 0;
const WIDTH_LEFT = 25;
const WIDTH_MIDDLE = 10;
const WIDTH_RIGHT = 70; // The longest text : [PHP] No other resources. [strlen(sha1('ca' . 'route' . config\AllConfig::$version . 'che'))]

// Check if we want one or all the routes
if (true === isset($argv[2]))
{
  $route = $argv[2];

  // If the route does not exist
  if (false === isset(\config\Routes::$_[$route]))
  {
    // We try to find a route which the name is similar
    require CORE_PATH . 'console/tools.php';
    list($newRoute) = guessWords($route, array_keys(\config\Routes::$_));

    // And asks the user whether we find what he wanted or not
    $choice = promptUser('There are no route with the name ' . CLI_WHITE . $route . CLI_YELLOW
      . ' ! Do you mean ' . CLI_WHITE . $newRoute . CLI_YELLOW . ' ? (y/n)');

    // If our guess is wrong, we apologise and exit !
    if ('n' === $choice)
    {
      echo CLI_RED, 'Sorry then !', END_COLOR, PHP_EOL;
      exit(1);
    }

    $route = $newRoute;
  }

  $routes = [$route => \config\Routes::$_[$route]];
} else
  $routes = \config\Routes::$_;

foreach($routes as $route => &$details)
{
  if ('otra_exception' === $route )
    continue;

  // Routes and paths management
  $chunks = $details['chunks'];
  $altColor = ($alt % 2) ? CLI_CYAN : CLI_LIGHT_CYAN;
  echo $altColor, sprintf('%-' . WIDTH_LEFT . 's', $route), str_pad('Url', WIDTH_MIDDLE, ' '), ': ' , $chunks[0], PHP_EOL;

  echo str_pad(' ', WIDTH_LEFT, ' '),
    str_pad('Path', WIDTH_MIDDLE, ' '),
    ': ' . $chunks[1] . '/' . $chunks[2] . '/' . $chunks[3] . 'Controller/' . $chunks[4],
    PHP_EOL;

  // shaName is the encrypted key that match a particular route / version
  $shaName = sha1('ca' . $route . config\AllConfig::$version . 'che');

  $basePath = substr(__DIR__, 0, -strlen('src/console')) . 'cache/';

  echo str_pad(' ', WIDTH_LEFT, ' '), 'Resources : ';

  // Resources management : show the state of each resource. Red => missing, green => exists
  if (true === isset($details['resources']))
  {
    $resources = $details['resources'];

    if (false === isset($resources['template']))
      showPHPState($basePath, $route, $altColor);

    if (true === isset($resources['_css']) || true === isset($resources['bundle_css']) ||true === isset($resources['module_css']))
      showResourceState('css', 'CSS', $basePath, $shaName, $altColor);

    if (true === isset($resources['_js']) || true === isset($resources['bundle_js']) || true === isset($resources['module_js']) || true === isset($resources['first_js']))
      showResourceState('js', 'JS', $basePath, $shaName, $altColor);

    if (true === isset($resources['template']))
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

  ++$alt;
}

?>
