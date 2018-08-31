<?
/** Task that show all or one of the routes available for the application.
 * It shows for each related route :
 * - the url
 * - the action
 * - the resources generated
 * - the key used for the cached file names
 */

/**
 * @param string $resourceExtension
 * @param string $resourceType
 * @param string $basePath
 * @param string $shaName
 * @param string $altColor
 */
function showResourceState(string $resourceExtension, string $resourceType, string &$basePath, string &$shaName, string &$altColor)
{
  echo (file_exists($basePath . $resourceExtension . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[', $resourceType, ']', $altColor;
}

$alt = 0;
const WIDTH_LEFT = 25;
const WIDTH_MIDDLE = 10;
const WIDTH_RIGHT = 70; // The longest text : [PHP] No other resources. [strlen(sha1('ca' . 'route' . config\AllConfig::$version . 'che'))]

// Check if we want one or all the routes
if (true === isset($argv[2]))
{
  if (false === isset(\config\Routes::$_[$argv[2]]))
  {
    echo redText('There are no route with the name \'' . $argv[2] . '\'.'), PHP_EOL;
    exit(1);
  }

  $routes = [$argv[2] => \config\Routes::$_[$argv[2]]];
} else
  $routes = \config\Routes::$_;

foreach($routes as $route => &$details)
{
  // Routes and paths management
  $chunks = $details['chunks'];
  $altColor = ($alt % 2) ? cyan() : lightCyan();
  echo $altColor, sprintf('%-' . WIDTH_LEFT . 's', $route), str_pad('Url', WIDTH_MIDDLE, ' '), ': ' , $chunks[0], PHP_EOL;

  if ('exception' !== $route )
    echo str_pad(' ', WIDTH_LEFT, ' '),
      str_pad('Path', WIDTH_MIDDLE, ' '),
      ': ' . $chunks[1] . '/' . $chunks[2] . '/' . $chunks[3] . 'Controller/' . $chunks[4],
      PHP_EOL;

  // shaName is the encrypted key that match a particular route / version
  $shaName = sha1('ca' . $route . config\AllConfig::$version . 'che');

  $basePath = substr(__DIR__, 0, -strlen('lib/myLibs/console')) . 'cache/';

  echo str_pad(' ', WIDTH_LEFT, ' '), 'Resources : ';

  if (true === isset($resources['template']))
    echo (file_exists($basePath . 'php' . '/' . $route. '.php') === true)
      ? lightGreen()
      : lightRed(), '[PHP]', $altColor;

  // Resources management : show the state of each ressource. Red => missing, green => exists
  if (true === isset($details['resources']))
  {
    $resources = $details['resources'];

    if (true === isset($resources['_css']) || true === isset($resources['bundle_css']) ||true === isset($resources['module_css']))
      showResourceState('css', 'CSS', $basePath, $shaName, $altColor);

    if (true === isset($resources['_js']) || true === isset($resources['bundle_js']) || true === isset($resources['module_js']) || true === isset($resources['first_js']))
      showResourceState('js', 'JS', $basePath, $shaName, $altColor);

    if (true === isset($resources['template']))
      showResourceState('tpl', 'TEMPLATE', $basePath, $shaName, $altColor);
  } else
    echo ' No other resources. ';

  echo '[', $shaName, ']', PHP_EOL, endColor(), str_repeat('-', WIDTH_LEFT + WIDTH_MIDDLE + WIDTH_RIGHT), PHP_EOL;

  ++$alt;
}

?>
