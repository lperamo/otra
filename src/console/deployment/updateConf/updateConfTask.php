<?php
declare(strict_types=1);

if (!defined('CHUNKS_KEY_LENGTH'))
  define('CHUNKS_KEY_LENGTH', 10); // length of the string "chunks'=>["

if (!function_exists('writeConfigFile'))
{
  /**
   * @param string $configFile
   * @param string $content
   */
  function writeConfigFile(string $configFile, string &$content)
  {
    if (true === empty($content))
    {
      echo CLI_YELLOW, 'Nothing to put into ', CLI_LIGHT_CYAN, $configFile, CLI_YELLOW,
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
    echo CLI_BLUE, 'BASE_PATH + ', CLI_LIGHT_CYAN, substr($configFile, strlen(BASE_PATH)), CLI_GREEN, ' updated.',
      END_COLOR, PHP_EOL;
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
      $key = (is_numeric($key)) ? '' : '\'' . $key . '\'' . '=>';

      if (!is_array($arrayChunk))
      {
        if (is_numeric($arrayChunk))
          $content .= $key . $arrayChunk . ',';
        elseif (false === $routeConfigFile)
          $content .= '\'' . addslashes($arrayChunk) . '\',';
        else
        {
          $arrayChunk = (is_bool($arrayChunk))
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
if (!defined('BUNDLES_PATH'))
  define('BUNDLES_PATH', BASE_PATH . 'bundles/');

$folderHandler = opendir(BUNDLES_PATH);
$securities = $configs = $routes = $schemas = [];

// we scan the bundles directory to retrieve all the bundles name ...
while (false !== ($file = readdir($folderHandler)))
{
  // 'config' and 'views' are not bundles ... just a configuration folder
  if (in_array($file, ['.', '..', 'config', 'views']))
    continue;

  $bundleDir = BUNDLES_PATH . $file;

  // We don't need the files either
  if (!is_dir($bundleDir))
    continue;

  // ... and we scan all those bundles to retrieve the config file names.
  $bundleConfigDir = $bundleDir . '/config/';
  $bundleConfigs = glob($bundleConfigDir . '*Config.php');
  $bundleRoutes = glob($bundleConfigDir . '*Routes.php');
  $bundleSchemas = glob($bundleConfigDir . 'data/yml/*Schema.yml');
  $bundleSecurities = glob($bundleConfigDir . '*security.php');

  if (!empty($bundleConfigs))
    $configs = array_merge($configs, $bundleConfigs);

  if (!empty($bundleRoutes))
    $routes = array_merge($routes, $bundleRoutes);

  if (!empty($bundleSecurities))
    $securities = array_merge($securities, $bundleSecurities);
}
closedir($folderHandler);

// now we have all the informations, we can create the files in 'bundles/config'
const BUNDLES_MAIN_CONFIG_DIR = BUNDLES_PATH . 'config/';
const SECURITIES_FOLDER = CACHE_PATH . 'php/security/';

if (!file_exists(BUNDLES_MAIN_CONFIG_DIR))
  mkdir(BUNDLES_MAIN_CONFIG_DIR, 0755);

/** CONFIGS MANAGEMENT */
$configsContent = '';

foreach ($configs as &$config)
  $configsContent .= file_get_contents($config);

writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Config.php', $configsContent);

/** ROUTES MANAGEMENT */
$routesArray = [];

foreach($routes as &$route)
  $routesArray = array_merge($routesArray, require $route);

// We check the order of routes path in order to avoid that routes like '/' override more complex rules by being in
// front of them
if (!function_exists('sortRoutes'))
{
  if (!defined('ROUTE_PATH'))
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

writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Routes.php', $routesContent);

/** SECURITIES MANAGEMENT */
$securitiesArray = [];

foreach($securities as &$route)
  $securitiesArray = array_merge($securitiesArray, require $route);

if (!file_exists(SECURITIES_FOLDER))
  mkdir(SECURITIES_FOLDER);

$securityContent = '<?php declare(strict_types=1);return ';

/**
 * @param array $configurationArray
 *
 * @return string
 */
function arrayExport(array $configurationArray) : string
{
  $content = '';

  foreach($configurationArray as $key => $value)
  {
    $content .= "'" . $key . '\'=>';

    if (is_array($value))
    {
      $content .= '[' . arrayExport($value) . ']';

      if (array_key_last($configurationArray) !== $key)
        $content .= ',';
    } else
    {
      $content .= '"' . $value . '"';

      if (array_key_last($configurationArray) !== $key)
        $content .= ',';
    }
  }

  return $content;
}

foreach($securitiesArray as $route => &$securityContent)
{
  $securityContent = '<?php declare(strict_types=1);return [' . arrayExport($securityContent) . '];';
  writeConfigFile(SECURITIES_FOLDER . $route . '.php', $securityContent);
}
