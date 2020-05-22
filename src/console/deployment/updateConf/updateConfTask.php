<?php
if (defined('CHUNKS_KEY_LENGTH') === false)
  define('CHUNKS_KEY_LENGTH', 10); // length of the string "chunks'=>["

if (function_exists('writeConfigFile') === false)
{
  function writeConfigFile(string &$configFile, string &$content)
  {
    if (true === empty($content))
    {
      echo CLI_YELLOW, 'Nothing to put into ', CLI_LIGHT_BLUE, $configFile, CLI_YELLOW,
        ' so we\'ll delete the main file if it exists.', END_COLOR, PHP_EOL;

      if (true === file_exists($configFile))
        unlink($configFile);

      return;
    }

    file_put_contents($configFile, $content);

    // Compresses the file
    file_put_contents(
      $configFile,
      rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($configFile))) . PHP_EOL
    );
    echo CLI_GREEN, $configFile, ' updated.', END_COLOR, PHP_EOL;
  }

  /**
   * We return a string (by altering it not with 'return') that contains an array with a PHP7 array like notation.
   *
   * @param string $content
   * @param array  $array
   * @param bool   $routeConfigFile
   * @param string $actualRouteKey
   */
  function loopForEach(string &$content, array &$array, bool $routeConfigFile = false, string $actualRouteKey = '')
  {
    foreach ($array as $key => &$arrayChunk)
    {
      $key = (true === is_numeric($key)) ? '' : '\'' . $key . '\'' . '=>';

      if (false === is_array($arrayChunk))
      {
        if (true === is_numeric($arrayChunk))
          $content .= $key . $arrayChunk . ',';
        elseif (false === $routeConfigFile)
          $content .= '\'' . addslashes($arrayChunk) . '\',';
        else
        {
          $arrayChunk = (true === is_bool($arrayChunk))
            ? (true === $arrayChunk) ? 'true' : 'false'
            : addslashes($arrayChunk);

          /* If it is a route config file then we search for the main pattern,
            namely the route part that doesn't contain parameters.
            Once found, we add it to the route configuration.
            It will help the router to go faster to name the parameters. */

          if ('\'chunks\'=>' === $actualRouteKey && false !== strpos($arrayChunk, '{'))
          {
            $bracketPosition = strpos($arrayChunk, '{');
            $mainPattern     = (false === $bracketPosition) ? $arrayChunk : substr($arrayChunk, 0, $bracketPosition);
            $content         = substr($content, 0, strlen($content) - CHUNKS_KEY_LENGTH) . 'mainPattern\'=>\'' . $mainPattern . '\', \'chunks\'=>[\'' . $arrayChunk . '\',';
          } else
          {
            $separator  = ('true' === $arrayChunk || 'false' === $arrayChunk) ? ' ' : '\'';
            $arrayChunk = $separator . $arrayChunk . $separator . ',';

            $content .= $key . $arrayChunk;
          }
        }

        continue;
      }

      // Case where the dev put, for example, 'bundle_js' => [] in the routes configuration file
      if ([] === $arrayChunk)
        continue;

      $content .= $key . '[';

      loopForEach($content, $arrayChunk, $routeConfigFile, $key);
      $content = substr($content, 0, -1);
      $content .= '],';
    }
  }
}

/** BEGINNING OF THE TASK */
$dir = BASE_PATH . 'bundles/';
$folderHandler = opendir($dir);
$configs = $routes = $schemas = [];

// we scan the bundles directory to retrieve all the bundles name ...
while (false !== ($file = readdir($folderHandler)))
{
  // 'config' and 'views' are not bundles ... just a configuration folder
  if (true === in_array($file, ['.', '..', 'config', 'views']))
    continue;

  $bundleDir = $dir . $file;

  // We don't need the files either
  if (true !== is_dir($bundleDir))
    continue;

  // ... and we scan all those bundles to retrieve the config file names.
  $bundleConfigDir = $bundleDir . '/config/';
  $bundleConfigs = glob($bundleConfigDir . '*Config.php');
  $bundleRoutes = glob($bundleConfigDir . '*Routes.php');
  $bundleSchemas = glob($bundleConfigDir . 'data/yml/*Schema.yml');

  if (false === empty($bundleConfigs))
    $configs = array_merge($configs, $bundleConfigs);

  if (false === empty($bundleRoutes))
    $routes = array_merge($routes, $bundleRoutes);
}
closedir($folderHandler);

// now we have all the informations, we can create the files in 'bundles/config'
$configDir = $dir . 'config/';
$configFile = $configDir . 'Config.php';
$routesFile = $configDir . 'Routes.php';

if (false === file_exists($configDir))
  mkdir($configDir, 0755);

$configsContent = '';

/** CONFIGS MANAGEMENT */
foreach ($configs as &$config)
  $configsContent .= file_get_contents($config);

writeConfigFile($configFile, $configsContent);

/** ROUTES MANAGEMENT */
$routesArray = [];

foreach($routes as &$route)
  $routesArray = array_merge($routesArray, require $route);

// We check the order of routes path in order to avoid that routes like '/' override more complex rules by being in
// front of them
if (function_exists('sortRoutes') === false)
{
  if(defined('ROUTE_PATH') === false)
    define ('ROUTE_PATH', 0);

  $sortRoutes = function (string $routeA, string $routeB) use ($routesArray)
  {
    /** @var array $routesArray */
    return (strlen($routesArray[$routeA]['chunks'][ROUTE_PATH]) <= strlen($routesArray[$routeB]['chunks'][ROUTE_PATH]))
      ? 1
      : -1;
  };
}

uksort($routesArray, $sortRoutes);

// Transforms the array in code that returns the array.
$routesContent = '<?php declare(strict_types=1);return [';
loopForEach($routesContent, $routesArray, true);
$routesContent = substr($routesContent, 0, -1) . '];';

writeConfigFile($routesFile, $routesContent);

