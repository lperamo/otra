<?
/** Task that show all or one of the routes available for the application.
 * It shows for each related route :
 * - the url
 * - the action
 * - the resources generated
 * - the key used for the cached file names
 */
$alt = 0;

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
  echo $altColor, sprintf('%-25s', $route), str_pad('Url', 10, ' '), ': ' , $chunks[0], PHP_EOL, PHP_EOL;

  if ('exception' !== $route ) {
    echo str_pad(' ', 25, ' '), str_pad('Path', 10, ' '), ': ' . $chunks[1] . '/' . $chunks[2] . '/' . $chunks[3] . 'Controller/' . $chunks[4], PHP_EOL;
  }

  $shaName = sha1('ca' . $route . config\All_Config::$version . 'che');

  $basePath = substr(__DIR__, 0, -15) . 'cache/';

  echo str_pad(' ', 25, ' '), 'Resources : ';
  echo (file_exists($basePath . 'php' . '/' . $route. '.php')) ? lightGreen() : lightRed(), '[PHP]', $altColor;

  // Resources management
  if(isset($details['resources']))
  {
    $resources = $details['resources'];

    if(isset($resources['_css']) || isset($resources['bundle_css']) || isset($resources['module_css']))
      echo (file_exists($basePath . 'css' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[CSS]', $altColor;

    if(isset($resources['_js']) || isset($resources['bundle_js']) || isset($resources['module_js']) || isset($resources['first_js']))
      echo (file_exists($basePath . 'js' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[JS]', $altColor;

    if(isset($resources['template']))
      echo (file_exists($basePath . 'tpl' . '/' . $shaName. '.gz')) ? lightGreen() : lightRed(), '[TEMPLATE]', $altColor;

    echo '[', $shaName, ']', PHP_EOL, endColor();
  } else
    echo ' No other resources. ', '[', $shaName, ']', PHP_EOL, endColor();

  ++$alt;
}

?>
