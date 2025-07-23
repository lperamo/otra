<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\buildDev;

use JsonException;
use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, SUCCESS};
use const otra\console\deployment\
{FILE_TASK_GCC,
  PATHS_TO_AVOID,
  PATHS_TO_HAVE_RESOURCES,
  RESOURCES_TO_WATCH,
  WATCH_FOR_CSS_RESOURCES,
  WATCH_FOR_PHP_FILES,
  WATCH_FOR_ROUTES,
  WATCH_FOR_TS_RESOURCES};
use function otra\console\deployment\
{genClassMap\genClassMap,
  generateJavaScript,
  generateStylesheetsFiles,
  isNotInThePath,
  updateConf\updateConf};

const
  BUILD_DEV_ARG_VERBOSE = 2,
  BUILD_DEV_ARG_SCOPE = 5,
  BUILD_DEV_ARG_ROUTE = 6,
  NO_SCOPE_TO_USE = -1;

/**
 * @param int         $scope
 * @param int         $verbose
 * @param array       $resources
 * @param string      $key
 * @param string      $bundlePath
 * @param string      $assetType
 * @param string|null $resourcePath
 *
 * @throws JsonException
 * @throws OtraException
 * @return bool
 */
function handleResource(int $scope, int $verbose, array $resources, string $key, 
  string $bundlePath, string $assetType, ?string $resourcePath = null)
: bool
{
  $filesProcessed = false;

  // If this kind of resource does not exist, we leave
  if (!isset($resources[$key]))
    return false;

  $isScss = ($assetType === 'css');
  $resourceType = $isScss ? 'scss' :'ts';  
  $tempPath = $bundlePath . ($resourcePath ?? '') . 'resources/';
  $resourcePath = $tempPath . ($isScss ? 'scss' : 'js') . DIR_SEPARATOR;
  $finalPathBase = $tempPath . ($isScss ? 'scss' : 'devJs') . DIR_SEPARATOR;

  foreach ($resources[$key] as $resourceKey => $resource)
  {
    $resourceName = is_array($resource) ? $resourceKey : $resource;

    // Skip HTTP resources
    if (str_contains($resourceName, 'http'))
      continue;

    $finalPath = $finalPathBase . $resourceName . '.' . $resourceType;

    if (!file_exists($finalPath))
      continue;

    if ($scope !== -1 && !shouldProcessResource($finalPath, $resourceType, $scope))
      continue;

    $lastSlashPosition = strrpos($resourceName, '/');
    $hasSlash = ($lastSlashPosition !== false);
    $baseName = $hasSlash ? substr($resourceName, $lastSlashPosition + 1) : $resourceName;

    if ($assetType === 'js')
      generateJavaScript(
        false,
        $verbose,
        FILE_TASK_GCC,
        $resourcePath,
        $baseName,
        $finalPath
      );
    else
    {
      generateStylesheetsFiles(
        $baseName,
        $tempPath,
        'scss' . DIR_SEPARATOR . substr(
          $finalPath,
          strpos($finalPath, $resourceName),
          $hasSlash ? $lastSlashPosition : strlen($resourceName)
        ) . '/',
        $finalPath,
        'scss',
        $verbose > 0
      );
    }
    $filesProcessed = true;
  }

  return $filesProcessed;
}

/**
 * @param int    $scope
 * @param        $resources
 * @param        $routeChunks
 * @param string $assetType
 * @param string $bundlePath
 * @param int    $verbose
 *
 * @return bool
 */
function handleResources(int $scope, $resources, $routeChunks, string $assetType, string $bundlePath, int $verbose): bool
{
  $filesProcessed = false;
  $filesProcessed |= handleResource($scope, $verbose, $resources, 'app_' . $assetType, BUNDLES_PATH, $assetType);
  $filesProcessed |= handleResource($scope, $verbose, $resources, 'bundle_' . $assetType, $bundlePath, $assetType);
  $filesProcessed |= handleResource(
    $scope,
    $verbose,
    $resources,
    'core_' . $assetType,
    CORE_PATH,
    $assetType,
    ''
  );
  $filesProcessed |= handleResource(
    $scope,
    $verbose,
    $resources,
    'module_' . $assetType,
    $bundlePath . $routeChunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR,
    $assetType
  );

  return (bool) ($filesProcessed | handleResource(
    $scope, 
    $verbose,
    $resources,
    'print_' . $assetType,
    $bundlePath . $routeChunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR,
    $assetType
  ));
}

function shouldProcessResource(string $realPath, string $extension, int $scope): bool
{
  // Check paths to avoid
  foreach (PATHS_TO_AVOID as $pathToAvoid)
  {
    if (str_contains($realPath, $pathToAvoid))
      return false;
  }

  // Check if it's a watched extension
  if (!isset(array_flip(RESOURCES_TO_WATCH)[$extension]))
    return false;

  // Skip starters
  if (str_contains($realPath, 'starters'))
    return false;

  // Apply scope filtering
  if (isNotInThePath(
    PATHS_TO_HAVE_RESOURCES,
    $realPath,
    ($scope === 0 && !str_contains($realPath, CORE_PATH)
      || $scope === 1 && str_contains($realPath, CORE_PATH)
      || $scope === 2)
  ))
    return false;

  return true;
}

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws JsonException|OtraException
 * @return void
 */
function buildDev(array $argumentsVector) : void
{
  require_once CORE_PATH . 'console/deployment/taskFileInit.php';

  // Reminder : 0 => no debug, 1 => basic logs, 2 => advanced logs with main events showed
  $verbose = (int) ($argumentsVector[BUILD_DEV_ARG_VERBOSE] ?? 0);
  $scope = (int) ($argumentsVector[BUILD_DEV_ARG_SCOPE] ?? 0);
  $routeName = $argumentsVector[BUILD_DEV_ARG_ROUTE] ?? '_all';
  
  if ($scope < 0 || $scope > 2)
  {
    echo CLI_ERROR, 'The scope must be between ', CLI_INFO_HIGHLIGHT, '0', CLI_ERROR, ' and ', CLI_INFO_HIGHLIGHT, '2' ,
      CLI_ERROR, '.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  $filesProcessed = false;

  // Handle PHP files
  if (WATCH_FOR_PHP_FILES)
  {
    // We generate the class mapping...
    // "_once ..." needed to avoid a repeatable function definition check
    require_once CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
    genClassMap([]);
    $filesProcessed = true;
  }

  if (WATCH_FOR_ROUTES)
  {
    // We update routes configuration if the PHP file is a routes' configuration file
    echo 'Launching routes update...', PHP_EOL;
    require_once CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
    updateConf('2');
    $filesProcessed = true;
  }

  require_once CONSOLE_PATH . 'deployment/generateOptimizedJavaScript.php';

  $routes = Routes::$allRoutes;

  if ($routeName !== '_all')
  {
    if (!isset($routes[$routeName]))
    {
      echo CLI_ERROR, 'There is no route ', CLI_INFO_HIGHLIGHT, $routeName, CLI_ERROR, '.', PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    $routes = [$routeName => $routes[$routeName]];
    $scope = NO_SCOPE_TO_USE;
  }

  $filesProcessed = false;

  foreach ($routes as $route)
  {
    if (!isset($route['resources']))
      continue;

    $resources = $route['resources'];
    $routeChunks = $route['chunks'];

    // `chunks` key may not exist in an OTRA route
    if (!isset($routeChunks[Routes::ROUTES_CHUNKS_BUNDLE]))
      continue;

    $bundlePath = BASE_PATH . 'bundles/' . $routeChunks[Routes::ROUTES_CHUNKS_BUNDLE] . DIR_SEPARATOR;

    if (WATCH_FOR_CSS_RESOURCES)
    {
      $assetType = 'css';

      if (str_contains(implode(array_keys($resources)), $assetType))
        $filesProcessed = $filesProcessed || handleResources($scope, $resources, $routeChunks, $assetType, $bundlePath, $verbose);      
    }

    if (WATCH_FOR_TS_RESOURCES)
    {
      $assetType = 'js';

      if (str_contains(implode(array_keys($resources)), $assetType))
        $filesProcessed = $filesProcessed || handleResources($scope, $resources, $routeChunks, $assetType, $bundlePath, $verbose);
    }
  }
  
  if ($filesProcessed)
  {
    if ($verbose === 0)
      echo CLI_BASE, 'Files have been generated', SUCCESS;
  } else
  {
    echo CLI_WARNING;

    if ($routeName === '_all')
      echo 'No files to process.';
    else
      echo 'No files to process for the route ', CLI_INFO_HIGHLIGHT, $routeName, CLI_WARNING, '.';

    echo END_COLOR, PHP_EOL;
  }
}
