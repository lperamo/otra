<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\updateConf;

use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, DEV, DIR_SEPARATOR, PROD};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_TABLE, CLI_WARNING, END_COLOR};

if (!file_exists(BUNDLES_PATH))
{
  echo CLI_ERROR, 'There is no bundles to update.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

const
  CHUNKS_KEY_LENGTH = 10, // length of the string "chunks'=>["
  UPDATE_CONF_ARG_ROUTE_NAME = 2,
  SINGLE_QUOTE = '\'';

if (!defined('otra\console\deployment\updateConf\UPDATE_CONF_ROUTE_NAME'))
  define('otra\console\deployment\updateConf\UPDATE_CONF_ROUTE_NAME', $argv[UPDATE_CONF_ARG_ROUTE_NAME] ?? null);

if (!function_exists('otra\console\deployment\updateConf\writeConfigFile'))
{
  /**
   * @param string $configFile
   * @param string $content
   */
  function writeConfigFile(string $configFile, string $content) : void
  {
    if (empty($content))
    {
      echo CLI_WARNING, 'Nothing to put into ', CLI_INFO_HIGHLIGHT, $configFile, CLI_WARNING,
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
    echo CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, substr($configFile, strlen(BASE_PATH)), CLI_BASE,
      ' updated', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
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
      $arrayKey = (is_numeric($arrayKey)) ? '' : SINGLE_QUOTE . $arrayKey . SINGLE_QUOTE . '=>';

      if (!is_array($arrayChunk))
      {
        if (is_numeric($arrayChunk))
          $content .= $arrayKey . ((string) $arrayChunk) . ',';
        elseif (!$isARouteConfigFile)
          $content .= SINGLE_QUOTE . addslashes($arrayChunk) . '\',';
        else
        {
          $isBoolArrayChunk = is_bool($arrayChunk);
          $arrayChunk = ($isBoolArrayChunk)
            ? ($arrayChunk ? 'true' : 'false')
            : addslashes($arrayChunk);

          /* If it is a route config file then we search for the main pattern,
            namely the route part that doesn't contain parameters.
            Once found, we add it to the route configuration.
            It will help the router to go faster to name the parameters. */

          if ('\'chunks\'=>' === $actualRouteKey && str_contains($arrayChunk, '{'))
          {
            $bracketPosition = mb_strpos($arrayChunk, '{');
            $mainPattern = (false === $bracketPosition)
              ? $arrayChunk
              : mb_substr($arrayChunk, 0, $bracketPosition);
            $content = mb_substr($content, 0, mb_strlen($content) - CHUNKS_KEY_LENGTH) . 'mainPattern\'=>\'' .
              $mainPattern . '\', \'chunks\'=>[\'' . $arrayChunk . '\',';
          } else
          {
            $separator  = $isBoolArrayChunk ? ' ' : SINGLE_QUOTE;
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
      $content = mb_substr($content, 0, -1);
      $content .= '],';
    }
  }
}

/** BEGINNING OF THE TASK */
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
    $bundleConfigDir . 'security/' . (UPDATE_CONF_ROUTE_NAME === null ? '*' : UPDATE_CONF_ROUTE_NAME . DIR_SEPARATOR),
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
const
  BUNDLES_MAIN_CONFIG_DIR = BUNDLES_PATH . 'config/',
  SECURITIES_FOLDER = CACHE_PATH . 'php/security/',
  OTRA_LABEL_SECURITY_NONE = "'none'",
  OTRA_LABEL_SECURITY_STRICT_DYNAMIC = "'strict-dynamic'",
  PHP_FILE_BEGINNING = '<?php declare(strict_types=1);return [';

if (!file_exists(BUNDLES_MAIN_CONFIG_DIR))
  mkdir(BUNDLES_MAIN_CONFIG_DIR, 0755);

/** CONFIGS MANAGEMENT */
$configsContent = '';

foreach ($configs as $config)
  $configsContent .= file_get_contents($config);

writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Config.php', $configsContent);

/** ROUTES MANAGEMENT */
$routesArray = [];

foreach($routes as $route)
  $routesArray = array_merge($routesArray, require $route);

unset($route);

// We check the order of routes path in order to avoid that routes like '/' override more complex rules by being in
// front of them
/** @var Closure $sortRoutes */
if (!function_exists('otra\console\deployment\updateConf\sortRoutes'))
{
  $sortRoutes = function (string $routeA, string $routeB) use ($routesArray) : int
  {
    /** @var array<string,array<string, array<int|string,string|array>>> $routesArray */
    return (strlen($routesArray[$routeA]['chunks'][Routes::ROUTES_CHUNKS_URL]) <= strlen($routesArray[$routeB]['chunks'][Routes::ROUTES_CHUNKS_URL]))
      ? 1
      : -1;
  };
}

uksort($routesArray, $sortRoutes);

// Transforms the array in code that returns the array.
$routesContent = PHP_FILE_BEGINNING;
loopForEach($routesContent, $routesArray, true);
$routesContent = substr($routesContent, 0, -1) . '];';

writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Routes.php', $routesContent);

/** SECURITIES MANAGEMENT */
$securitiesArray = [];

const
  OTRA_PHP_DOT_EXTENSION = '.php',
  OTRA_SECURITY_DEV_FOLDER = SECURITIES_FOLDER . DEV . DIR_SEPARATOR,
  OTRA_SECURITY_PROD_FOLDER = SECURITIES_FOLDER . PROD . DIR_SEPARATOR,
  OTRA_END_FILE = '];';

require CORE_PATH . 'services/securityService.php';

foreach($securities as $securityFileConfigFolder)
{
  $devSecurityFile = $securityFileConfigFolder . DIR_SEPARATOR . DEV . OTRA_PHP_DOT_EXTENSION;
  $prodSecurityFile = $securityFileConfigFolder . DIR_SEPARATOR . PROD . OTRA_PHP_DOT_EXTENSION;
  $securityBaseFolderArray = basename($securityFileConfigFolder);

  if (file_exists($devSecurityFile))
    $securitiesArray[$securityBaseFolderArray][DEV] = require $devSecurityFile;

  if (file_exists($prodSecurityFile))
    $securitiesArray[$securityBaseFolderArray][PROD] = require $prodSecurityFile;
}

// we ensure that security folders exist
if (!file_exists(OTRA_SECURITY_DEV_FOLDER))
  mkdir(OTRA_SECURITY_DEV_FOLDER, 0777, true);

if (!file_exists(OTRA_SECURITY_PROD_FOLDER))
  mkdir(OTRA_SECURITY_PROD_FOLDER, 0777, true);

if (!function_exists('otra\console\deployment\updateConf\arrayExport'))
{
  /**
   * @param array{
   *   csp?: array<string,string>,
   *   permissionsPolicy?: array<string,string>
   * }|array<string,string> $configurationArray
   *
   * @return string
   */
  function arrayExport(array $configurationArray) : string
  {
    $content = '';

    foreach($configurationArray as $policyType => $value)
    {
      $content .= "'" . $policyType . '\'=>' . (is_array($value)
        ? '[' . arrayExport($value) . ']'
        : '"' . $value . '"');

      if (array_key_last($configurationArray) !== $policyType)
        $content .= ',';
    }

    return $content;
  }
}

foreach($securitiesArray as $route => $securityArray)
{
  $fileName = $route . OTRA_PHP_DOT_EXTENSION;

  // dev environment
  if (isset($securityArray[DEV]))
  {
    writeConfigFile(
      OTRA_SECURITY_DEV_FOLDER . $fileName,
      PHP_FILE_BEGINNING . arrayExport($securityArray[DEV]) . OTRA_END_FILE
    );
  }

  // prod environment
  if (isset($securityArray[PROD]))
  {
    writeConfigFile(
      OTRA_SECURITY_PROD_FOLDER . $fileName,
      PHP_FILE_BEGINNING . arrayExport($securityArray[PROD]) . OTRA_END_FILE
    );
  }
}

