<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\updateConf;

use FilesystemIterator;
use otra\config\Routes;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use const otra\cache\php\
{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_PATH, DEV, DIR_SEPARATOR, PROD};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_TABLE, CLI_WARNING, END_COLOR};
use const otra\src\console\deployment\updateConf\{
  UPDATE_CONF_MASK_ALL,
  UPDATE_CONF_MASK_ALL_CONFIG,
  UPDATE_CONF_MASK_ROUTES,
  UPDATE_CONF_MASK_SCHEMA,
  UPDATE_CONF_MASK_FIXTURES,
  UPDATE_CONF_MASK_SECURITIES
};
use function otra\console\deployment\genJsRouting\genJsRouting;
use function otra\src\tools\debug\validateYaml;
use function otra\tools\files\{compressPHPFile, returnLegiblePath, returnLegiblePath2};

require_once CONSOLE_PATH . 'deployment/updateConf/updateConfConstants.php';

const
  CHUNKS_KEY_LENGTH = 10, // length of the string "chunks'=>["
  UPDATE_CONF_ARG_MASK = 2,
  UPDATE_CONF_ARG_ROUTE_NAME = 3,
  SINGLE_QUOTE = '\'',
  BUNDLES_MAIN_CONFIG_DIR = BUNDLES_PATH . 'config/',
  SECURITIES_FOLDER = CACHE_PATH . 'php/security/',
  OTRA_LABEL_SECURITY_NONE = "'none'",
  OTRA_LABEL_SECURITY_STRICT_DYNAMIC = "'strict-dynamic'",
  PHP_FILE_BEGINNING = '<?php declare(strict_types=1);return [',
  CONFIG_FOLDER = '/config/',
  CONFIG_FILE_PATTERN = '*Config.php',
  SCHEMA_FILE_PATTERN = '*schema.yml',
  FIXTURES_FILES_PATTERN = 'fixtures/*.yml',
  NOT_MODULE_FOLDERS = ['.', '..', 'config', 'tasks', 'views'],
  PATH_CONFIG_FIXTURES = 'config/fixtures/',
  PATH_CONFIG_DATA_YML = 'config/data/yml/',
  MAIN_JS_ROUTING = BUNDLES_PATH . 'resources/js/' . 'jsRouting.js';

/**
 *
 * @throws OtraException
 */
function updateConf(?string $mask = null, ?string $routeName = null)
{
  if (!file_exists(BUNDLES_PATH))
  {
    echo CLI_ERROR, 'There is no bundles to update.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  $updateConfMask = (int) ($mask ?? UPDATE_CONF_MASK_ALL);
  $updateConfRouteName = $routeName ?? null;
  $updateConfAllConfig = $updateConfMask & UPDATE_CONF_MASK_ALL_CONFIG;
  $updateConfRoutes = $updateConfMask & UPDATE_CONF_MASK_ROUTES;
  $updateConfSecurities = $updateConfMask & UPDATE_CONF_MASK_SECURITIES;
  $updateConfSchema = $updateConfMask & UPDATE_CONF_MASK_SCHEMA;
  $updateConfFixtures = $updateConfMask & UPDATE_CONF_MASK_FIXTURES;

  if ($updateConfMask === 0)
  {
    echo CLI_WARNING, 'If you do not want to update anything then why launch this task in the first place?', END_COLOR,
    PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  if ($updateConfMask > UPDATE_CONF_MASK_ALL)
  {
    echo CLI_WARNING, 'Mask too big. Type ' . CLI_INFO_HIGHLIGHT . 'otra help updateConf' . CLI_WARNING .
      ' for more information.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  /** BEGINNING OF THE TASK */
  $securities = $configs = $routes = $schemas = $fixtures = [];

  // we scan the bundles' directory to retrieve all the bundles name ...
  searchFilesInFolder(
    BUNDLES_PATH,
    $securities,
    $configs,
    $routes,
    $schemas,
    $fixtures,
    $updateConfRouteName,
    $updateConfAllConfig,
    $updateConfRoutes,
    $updateConfSecurities,
    $updateConfSchema,
    $updateConfFixtures
  );

  // now we have all the information, we can create the files in 'bundles/config'
  if (!file_exists(BUNDLES_MAIN_CONFIG_DIR))
    mkdir(BUNDLES_MAIN_CONFIG_DIR, 0755);

  /** CONFIGS MANAGEMENT */
  if ($updateConfAllConfig !== 0)
  {
    $configsContent = '';

    foreach ($configs as $config)
      $configsContent .= file_get_contents($config);

    // Removes PHP beginning tag
    $configsContent = str_replace('<?php', '', $configsContent);

    // Searches for use statements
    preg_match_all(
      '@^\\s*use\\s*([^;]*);\\s*$@mx',
      $configsContent,
      $matches,
      PREG_OFFSET_CAPTURE
    );

    array_unshift($matches);

    // Moving the use statements to the top of the file and removes doubloons
    $useStatements = [];
    $delta = 0;

    foreach ($matches[0] as $match)
    {
      $useStatement = trim($match[0]);

      // We do not already have this use statement
      if (!in_array($useStatement, $useStatements))
        $useStatements [] = trim($match[0]);

      $length = mb_strlen($match[0]);
      $configsContent = substr_replace($configsContent, '', $match[1] - $delta, $length);
      $delta += $length;
    }

    $configsContent = '<?php declare(strict_types=1);namespace otra\\cache\\php;' .
      implode('', $useStatements) . $configsContent;
    define(__NAMESPACE__ . '\\MAIN_CONFIG_FILE', BUNDLES_MAIN_CONFIG_DIR . 'Config.php');
    writeConfigFile(MAIN_CONFIG_FILE, $configsContent);

    // Compresses the config file
    require CORE_PATH . 'tools/files/compressPhpFile.php';
    compressPHPFile(MAIN_CONFIG_FILE, MAIN_CONFIG_FILE);
  }

  /** ROUTES MANAGEMENT */
  if ($updateConfRoutes !== 0)
  {
    $routesArray = $routesFunctions = [];

    foreach($routes as $route)
    {
      // If we call multiple times task that launch `updateConf`, for example, we need `require_once` instead of
      // `require`
      require_once $route;

      $routesFunctionsToAdd = substr(
        str_replace(
          [BASE_PATH, '/', '.php'],
          ['', '\\', ''],
          $route
        ),
        0,
        -6
      );

      if ($routesFunctionsToAdd[strlen($routesFunctionsToAdd) - 1] === '\\')
        $routesFunctionsToAdd .= 'routes';

      $routesFunctions[] = $routesFunctionsToAdd . '\\getRoutes';
    }

    foreach($routesFunctions as $routeFunction)
      $routesArray = array_merge($routesArray, call_user_func($routeFunction));

    unset($route, $routeFunction);

    // We check the order of routes path in order to avoid that routes like '/' override more complex rules by being in
    // front of them
    /** @var Closure $sortRoutes */
    if (!function_exists(__NAMESPACE__ . '\\sortRoutes'))
    {
      $sortRoutes = fn(string $routeA, string $routeB) : int =>
        /** @var array<string,array<string, array<int|string,string|array>>> $routesArray */
        (strlen($routesArray[$routeA]['chunks'][Routes::ROUTES_CHUNKS_URL]) <= strlen($routesArray[$routeB]['chunks'][Routes::ROUTES_CHUNKS_URL]))
          ? 1
          : -1;
    }

    uksort($routesArray, $sortRoutes);

    // Transforms the array in code that returns the array.
    $routesContent = PHP_FILE_BEGINNING;

    if (!empty($routesArray))
    {
      loopForEach($routesContent, $routesArray, true);
      $routesContent = substr($routesContent, 0, -1);
    }

    writeConfigFile(BUNDLES_MAIN_CONFIG_DIR . 'Routes.php', $routesContent . '];');

    if (file_exists(MAIN_JS_ROUTING))
    {
      require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
      require_once CORE_PATH . 'console/deployment/genJsRouting/genJsRoutingTask.php';
      echo 'Updating JavaScript routing in ', CLI_INFO_HIGHLIGHT, returnLegiblePath2(MAIN_JS_ROUTING), CLI_BASE,
        '.', END_COLOR, PHP_EOL;
      genJsRouting();
    }
  }

  /** SECURITIES MANAGEMENT */
  if ($updateConfSecurities !== 0)
  {
    $securitiesArray = [];

    define(__NAMESPACE__ . '\\OTRA_PHP_DOT_EXTENSION', '.php');
    define(__NAMESPACE__ . '\\OTRA_SECURITY_DEV_FOLDER', SECURITIES_FOLDER . DEV . DIR_SEPARATOR);
    define(__NAMESPACE__ . '\\OTRA_SECURITY_PROD_FOLDER', SECURITIES_FOLDER . PROD . DIR_SEPARATOR);
    define(__NAMESPACE__ . '\\OTRA_END_FILE', '];');

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

    if (!function_exists(__NAMESPACE__ . '\\arrayExport'))
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
  }

  /** SCHEMAS MANAGEMENT */
  if ($updateConfSchema !== 0)
  {
    $mainYamlFile = BUNDLES_PATH . 'config/schema.yml';
    $schemas = array_merge(
      $schemas,
      glob(BUNDLES_PATH . 'config/data/yml/schema.yml')
    );

    if (!empty($schemas))
    {
      $schemaContent = '';

      foreach($schemas as $schema)
      {
        $schemaContent .= file_get_contents($schema);
      }

      require CORE_PATH . 'tools/debug/validateYaml.php';
      validateYaml($schemaContent, $mainYamlFile);

      writeConfigFile(BUNDLES_PATH . 'config/schema.yml', $schemaContent, false);
    } else
    {
      require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
      echo CLI_WARNING . 'Nothing to put into ', CLI_INFO_HIGHLIGHT, returnLegiblePath($mainYamlFile), CLI_WARNING,
        ' so we\'ll delete this file if it exists.', END_COLOR, PHP_EOL;

      if (file_exists($mainYamlFile))
        unlink($mainYamlFile);
    }
  }

  /** FIXTURES MANAGEMENT */
  if ($updateConfFixtures !== 0)
  {
    $fixtures = array_merge(
      $fixtures,
      glob(BUNDLES_PATH . PATH_CONFIG_DATA_YML . FIXTURES_FILES_PATTERN)
    );

    $fixturesFolder = BUNDLES_PATH . PATH_CONFIG_FIXTURES;

    if (!file_exists($fixturesFolder) && !mkdir($fixturesFolder))
    {
      require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
      echo CLI_ERROR, 'Cannot create the folder ', CLI_INFO_HIGHLIGHT, returnLegiblePath($fixturesFolder), CLI_ERROR,
        '.', END_COLOR, PHP_EOL;

      throw new OtraException(code: 1, exit: true);
    }

    foreach($fixtures as $fixture)
    {
      copy($fixture, BUNDLES_PATH . PATH_CONFIG_FIXTURES . basename($fixture));
    }
  }
}

/**
 *
 * @return void
 */
function searchFilesInFolder(
  string $folderPath,
  array &$securities,
  array &$configs,
  array &$routes,
  array &$schemas,
  array &$fixtures,
  ?string $updateConfRouteName,
  int $updateConfAllConfig,
  int $updateConfRoutes,
  int $updateConfSecurities,
  int $updateConfSchema,
  int $updateConfFixtures
): void
{
  $folderHandler = opendir($folderPath);

  // we scan the bundles' directory to retrieve all the bundles name ...
  while (false !== ($filename = readdir($folderHandler)))
  {
    // 'config', 'resources', 'tasks' and 'views' are not bundles ...
    if (in_array($filename, ['.', '..', 'config', 'repositories', 'resources', 'tasks', 'views']))
      continue;

    $actualFolder = $folderPath . $filename;

    // We don't need the files either
    if (!is_dir($actualFolder))
      continue;

    // ... and we scan all those bundles to retrieve the config file names.
    $bundleConfigDir = $actualFolder . CONFIG_FOLDER;

    if ($updateConfAllConfig !== 0)
    {
      $bundleConfigs = glob($bundleConfigDir . CONFIG_FILE_PATTERN);

      if (!empty($bundleConfigs))
        $configs = array_merge($configs, $bundleConfigs);

      $moduleFolderHandler = opendir($actualFolder);

      // we scan the bundles' directory to retrieve all the bundles name ...
      while (false !== ($filename = readdir($moduleFolderHandler)))
      {
        // 'config', 'tasks' and 'views' are not modules ...
        if (in_array($filename, NOT_MODULE_FOLDERS))
          continue;

        $moduleDir = $actualFolder . '/' . $filename;

        // We don't need the files either
        if (!is_dir($moduleDir))
          continue;

        $moduleConfig = glob($moduleDir . CONFIG_FOLDER . CONFIG_FILE_PATTERN);

        if (!empty($moduleConfig))
          $configs = array_merge($configs, $moduleConfig);
      }
    }

    if ($updateConfRoutes !== 0)
    {
      foreach (new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($bundleConfigDir, FilesystemIterator::SKIP_DOTS)
        ) as $file)
      {
        if ($file->isFile() && str_ends_with($file->getFilename(), 'Routes.php'))
          $bundleRoutes[] = $file->getPathname();
      }

      if (!empty($bundleRoutes))
        $routes = array_merge($routes, $bundleRoutes);

      $moduleFolderHandler = opendir($actualFolder);
      $moduleRoutes = [];

      // we scan the bundles' directory to retrieve all the bundles name ...
      while (false !== ($filename = readdir($moduleFolderHandler)))
      {
        // 'config', 'tasks' and 'views' are not modules ...
        if (in_array($filename, NOT_MODULE_FOLDERS))
          continue;

        $moduleDir = $actualFolder . '/' . $filename;

        // We don't need the files either
        if (!is_dir($moduleDir))
          continue;

        $moduleRoutes = glob($moduleDir . '/config/Routes.php');

        if (!empty($moduleRoutes))
          $routes = array_merge($routes, $moduleRoutes);
      }
    }

    if ($updateConfSecurities !== 0)
    {
      $bundleSecurities = glob(
        $bundleConfigDir . 'security/' . ($updateConfRouteName === null ? '*' : $updateConfRouteName . DIR_SEPARATOR),
        GLOB_ONLYDIR
      );

      if (!empty($bundleSecurities))
        $securities = array_merge($securities, $bundleSecurities);
    }

    if ($updateConfSchema || $updateConfFixtures)
    {
      $ymlBundlePath = $bundleConfigDir . 'data/yml/';

      if ($updateConfSchema !== 0)
      {
        $bundleSchemas = glob($ymlBundlePath . SCHEMA_FILE_PATTERN);

        if (!empty($bundleSchemas))
          $schemas = array_merge($schemas, $bundleSchemas);

        $moduleSchemas = [];
      }

      if ($updateConfFixtures)
      {
        $bundleFixtures = glob($ymlBundlePath . FIXTURES_FILES_PATTERN);

        if (!empty($bundleFixtures))
          $fixtures = array_merge($fixtures, $bundleFixtures);

        $moduleFixtures = [];
      }

      $moduleFolderHandler = opendir($actualFolder);

      // we scan the bundles' directory to retrieve all the bundles name ...
      while (false !== ($filename = readdir($moduleFolderHandler)))
      {
        // 'config', 'tasks' and 'views' are not modules ...
        if (in_array($filename, NOT_MODULE_FOLDERS))
          continue;

        $moduleDir = $actualFolder . '/' . $filename . '/';

        // We don't need the files either
        if (!is_dir($moduleDir))
          continue;

        if ($updateConfSchema !== 0)
        {
          $moduleSchemas = glob($moduleDir . PATH_CONFIG_DATA_YML . SCHEMA_FILE_PATTERN);

          if (!empty($moduleSchemas))
            $schemas = array_merge($schemas, $moduleSchemas);
        }

        if ($updateConfFixtures !== 0)
        {
          $moduleFixtures = glob($moduleDir . PATH_CONFIG_DATA_YML . FIXTURES_FILES_PATTERN);

          if (!empty($moduleFixtures))
            $fixtures = array_merge($fixtures, $moduleFixtures);
        }
      }
    }
  }

  closedir($folderHandler);
}

/**
 * If the content is empty, we remove the file configuration if it exists otherwise we update the file.
 */
function writeConfigFile(string $configFile, string $content, bool $toCompress = true) : void
{
  if (empty($content))
  {
    require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
    echo CLI_WARNING, 'Nothing to put into ', CLI_INFO_HIGHLIGHT, returnLegiblePath($configFile), CLI_WARNING,
      ' so we\'ll delete this file if it exists.', END_COLOR, PHP_EOL;

    if (file_exists($configFile))
      unlink($configFile);

    return;
  }

  file_put_contents($configFile, $content);

  // Compresses the file if needed (cannot do that for YAML)
  if ($toCompress)
  {
    file_put_contents(
      $configFile,
      rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($configFile))) . PHP_EOL
    );
  }

  echo CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, substr($configFile, strlen(BASE_PATH)), CLI_BASE,
  ' updated', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
}

/**
 * We return a string (by altering it not with 'return') that contains an array with a PHP7 array like notation.
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
