<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

if (!defined('CHUNKS_KEY_LENGTH'))
{
  define('CHUNKS_KEY_LENGTH', 10); // length of the string "chunks'=>["
  define('UPDATE_CONF_ARG_ROUTE_NAME', 2);
  define('UPDATE_CONF_ROUTE_NAME', $argv[UPDATE_CONF_ARG_ROUTE_NAME] ?? null);
}

if (!function_exists('writeConfigFile'))
{
  /**
   * @param string $configFile
   * @param string $content
   */
  function writeConfigFile(string $configFile, string $content) : void
  {
    if (empty($content))
    {
      echo CLI_YELLOW, 'Nothing to put into ', CLI_LIGHT_CYAN, $configFile, CLI_YELLOW,
        ' so we\'ll delete the main file if it exists.', END_COLOR, PHP_EOL;

      if (file_exists($configFile))
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
   * @param bool   $isARouteConfigFile
   * @param string $actualRouteKey
   */
  function loopForEach(
    string &$content,
    array &$array,
    bool $isARouteConfigFile = false,
    string $actualRouteKey = ''
  ) : void
  {
    /**
     * @var int|string $key
     * @var mixed      $arrayChunk
     */
    foreach ($array as $arrayKey => &$arrayChunk)
    {
      $arrayKey = (is_numeric($arrayKey)) ? '' : '\'' . $arrayKey . '\'' . '=>';

      if (!is_array($arrayChunk))
      {
        if (is_numeric($arrayChunk))
          $content .= $arrayKey . $arrayChunk . ',';
        elseif (!$isARouteConfigFile)
          $content .= '\'' . addslashes($arrayChunk) . '\',';
        else
        {
          $isBoolArrayChunk = is_bool($arrayChunk);
          $arrayChunk = ($isBoolArrayChunk)
            ? (true === $arrayChunk) ? 'true' : 'false'
            : addslashes($arrayChunk);

          /* If it is a route config file then we search for the main pattern,
            namely the route part that doesn't contain parameters.
            Once found, we add it to the route configuration.
            It will help the router to go faster to name the parameters. */

          if ('\'chunks\'=>' === $actualRouteKey && str_contains($arrayChunk, '{'))
          {
            $bracketPosition = strpos($arrayChunk, '{');
            $mainPattern = (false === $bracketPosition)
              ? $arrayChunk
              : substr($arrayChunk, 0, $bracketPosition);
            $content = substr($content, 0, strlen($content) - CHUNKS_KEY_LENGTH) . 'mainPattern\'=>\'' .
              $mainPattern . '\', \'chunks\'=>[\'' . $arrayChunk . '\',';
          } else
          {
            $separator  = $isBoolArrayChunk ? ' ' : '\'';
            $arrayChunk = $separator . $arrayChunk . $separator . ',';
            $content .= $arrayKey . $arrayChunk;
          }
        }

        continue;
      }

      // Case where the dev put, for example, 'bundle_js' => [] in the routes configuration file
      if ([] === $arrayChunk)
        continue;

      $content .= $arrayKey . '[';

      loopForEach($content, $arrayChunk, $isARouteConfigFile, $arrayKey);
      $content = substr($content, 0, -1);
      $content .= '],';
    }
  }
}

/** BEGINNING OF THE TASK */
if (!defined('BUNDLES_PATH'))
  define('BUNDLES_PATH', BASE_PATH . 'bundles/');

$folderHandler = opendir(BUNDLES_PATH);
$securities = $configs = $routes = [];

// we scan the bundles directory to retrieve all the bundles name ...
while (false !== ($filename = readdir($folderHandler)))
{
  // 'config' and 'views' are not bundles ... just a configuration folder
  if (in_array($filename, ['.', '..', 'config', 'views']))
    continue;

  $bundleDir = BUNDLES_PATH . $filename;

  // We don't need the files either
  if (!is_dir($bundleDir))
    continue;

  // ... and we scan all those bundles to retrieve the config file names.
  $bundleConfigDir = $bundleDir . '/config/';
  $bundleConfigs = glob($bundleConfigDir . '*Config.php');
  $bundleRoutes = glob($bundleConfigDir . '*Routes.php');
  $bundleSecurities = glob(
    $bundleConfigDir . 'security/' . (UPDATE_CONF_ROUTE_NAME === null ? '*' : UPDATE_CONF_ROUTE_NAME . '/'),
    GLOB_ONLYDIR
  );

  if (!empty($bundleConfigs))
    $configs = array_merge($configs, $bundleConfigs);

  if (!empty($bundleRoutes))
    $routes = array_merge($routes, $bundleRoutes);

  if (!empty($bundleSecurities))
    $securities = array_merge($securities, $bundleSecurities);
}
closedir($folderHandler);

// now we have all the informations, we can create the files in 'bundles/config'
if (!defined('BUNDLES_MAIN_CONFIG_DIR'))
{
  define('BUNDLES_MAIN_CONFIG_DIR', BUNDLES_PATH . 'config/');
  define('SECURITIES_FOLDER', CACHE_PATH . 'php/security/');
}

if (!defined('OTRA_LABEL_SECURITY_NONE'))
{
  define('OTRA_LABEL_SECURITY_NONE', "'none'");
  define('OTRA_LABEL_SECURITY_SELF', "'self'");
  define('OTRA_LABEL_SECURITY_STRICT_DYNAMIC', "'strict-dynamic'");
}

if (!file_exists(BUNDLES_MAIN_CONFIG_DIR))
  mkdir(BUNDLES_MAIN_CONFIG_DIR, 0755);

/** CONFIGS MANAGEMENT */
$configsContent = '';

foreach ($configs as $config)
  $configsContent .= file_get_contents($config);

writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Config.php', $configsContent);

/** ROUTES MANAGEMENT */
$routesArray = [];

foreach($routes as &$route)
  $routesArray = array_merge($routesArray, require $route);

// We check the order of routes path in order to avoid that routes like '/' override more complex rules by being in
// front of them
/** @var Closure $sortRoutes */
if (!function_exists('sortRoutes'))
{
  if (!defined('ROUTE_PATH'))
    define('ROUTE_PATH', 0);

  $sortRoutes = function (string $routeA, string $routeB) use ($routesArray) : int
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

if (!defined('OTRA_DEVELOPMENT_ENVIRONMENT'))
{
  define('OTRA_DEVELOPMENT_ENVIRONMENT', 'dev');
  define('OTRA_PRODUCTION_ENVIRONMENT', 'prod');
  define('OTRA_PHP_DOT_EXTENSION', '.php');
}

foreach($securities as $securityFileConfigFolder)
{
  $devSecurityFile = $securityFileConfigFolder . '/' . OTRA_DEVELOPMENT_ENVIRONMENT .
    OTRA_PHP_DOT_EXTENSION;

  if (file_exists($devSecurityFile))
    $securitiesArray[basename($securityFileConfigFolder)][OTRA_DEVELOPMENT_ENVIRONMENT] = require $devSecurityFile;

  $prodSecurityFile = $securityFileConfigFolder . '/' . OTRA_PRODUCTION_ENVIRONMENT .
    OTRA_PHP_DOT_EXTENSION;

  if (file_exists($prodSecurityFile))
    $securitiesArray[basename($securityFileConfigFolder)][OTRA_PRODUCTION_ENVIRONMENT] = require $prodSecurityFile;
}

// we ensure that security folders are
if (!defined('OTRA_SECURITY_DEV_FOLDER'))
{
  define('OTRA_SECURITY_DEV_FOLDER', SECURITIES_FOLDER . 'dev/');
  define('OTRA_SECURITY_PROD_FOLDER', SECURITIES_FOLDER . 'prod/');
  define('OTRA_BEGINNING_OF_CONFIG_FILE', '<?php declare(strict_types=1);return [');
}

if (!file_exists(OTRA_SECURITY_DEV_FOLDER))
  mkdir(OTRA_SECURITY_DEV_FOLDER, 0777, true);

if (!file_exists(OTRA_SECURITY_PROD_FOLDER))
  mkdir(OTRA_SECURITY_PROD_FOLDER, 0777, true);

$securityContent = '<?php declare(strict_types=1);return ';

if (!function_exists('arrayExport'))
{
  /**
   * @param array $configurationArray
   *
   * @return string
   */
  function arrayExport(array $configurationArray) : string
  {
    $content = '';

    /**
     * @var string $arrayKey
     * @var array|string $value
     */
    foreach($configurationArray as $arrayKey => $value)
    {
      $content .= "'" . $arrayKey . '\'=>';

      if (is_array($value))
      {
        $content .= '[' . arrayExport($value) . ']';

        if (array_key_last($configurationArray) !== $arrayKey)
          $content .= ',';
      } else
      {
        $content .= '"' . $value . '"';

        if (array_key_last($configurationArray) !== $arrayKey)
          $content .= ',';
      }
    }

    return $content;
  }
}

foreach($securitiesArray as $route => $securityArray)
{
  $fileName = $route . '.php';

  // dev environment
  if (isset($securityArray[OTRA_DEVELOPMENT_ENVIRONMENT]))
  {
    writeConfigFile(
      OTRA_SECURITY_DEV_FOLDER . $fileName,
      OTRA_BEGINNING_OF_CONFIG_FILE . arrayExport($securityArray[OTRA_DEVELOPMENT_ENVIRONMENT]) . '];'
    );
  }

  // prod environment
  if (isset($securityArray[OTRA_PRODUCTION_ENVIRONMENT]))
  {
    writeConfigFile(
      OTRA_SECURITY_PROD_FOLDER . $fileName,
      OTRA_BEGINNING_OF_CONFIG_FILE . arrayExport($securityArray[OTRA_PRODUCTION_ENVIRONMENT]) . '];'
    );
  }
}
