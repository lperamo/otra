<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genAssets;

use FilesystemIterator;
use JsonException;
use otra\config\Routes;
use JetBrains\PhpStorm\Pure;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use const otra\cache\php\
{APP_ENV, BASE_PATH, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_CSS_PATH, CORE_JS_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\config\VERSION;
use const otra\console\
{
  CLI_ERROR,
  CLI_GRAY,
  CLI_INFO,
  CLI_INFO_HIGHLIGHT,
  CLI_SUCCESS,
  CLI_WARNING,
  END_COLOR};
use function otra\src\console\deployment\googleClosureCompile;
use function otra\tools\gzCompressFile;

const
  GEN_ASSETS_ARG_ASSETS_MASK = 2,
  GEN_ASSETS_ARG_ROUTE = 4,
  GZIP_COMPRESSION_LEVEL = 9,

  GEN_ASSETS_MASK_TEMPLATE = 1,
  GEN_ASSETS_MASK_CSS = 2,
  GEN_ASSETS_MASK_JS = 4,
  GEN_ASSETS_MASK_MANIFEST = 8,
  GEN_ASSETS_MASK_SVG = 16,
  GEN_ASSETS_MASK_TOTAL = 31,

  OTRA_UNLINK_CALLBACK = 'unlink',
  OTRA_CLI_INFO_STRING = 'CLI_INFO',
  OTRA_LABEL_TSCONFIG_JSON = 'tsconfig.json';

/**
 * @param string $folder  The resource folder name
 * @param string $shaName The route's name encoded in sha1
 */
function unlinkResourceFile(string $folder, string $shaName) : void
{
  $fileName = CACHE_PATH . $folder . $shaName . '.gz';

  if (file_exists($fileName))
    unlink($fileName);
}

function status(string $status, string $color = 'CLI_SUCCESS') : string
{
  return ' [' . constant('otra\\console\\' . $color) . $status . CLI_GRAY. ']';
}

/**
 * @param string $assetType 'css' or 'js'
 *
 * @return null|string Return the path of the 'macro' resource file
 */
function loadAndSaveResources(
  array $resources,
  array $routeChunks,
  string $assetType,
  string $bundlePath,
  string $shaName
) : ?string
{
  ob_start();
  loadResource($resources, $routeChunks, 'app_' . $assetType, 'bundles/');
  loadResource($resources, $routeChunks, 'bundle_' . $assetType, $bundlePath, '');
  loadResource(
    $resources,
    $routeChunks,
    'core_' . $assetType,
    CORE_PATH,
    ''
  );
  loadResource(
    $resources,
    $routeChunks,
    'module_' . $assetType,
    $bundlePath . $routeChunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR
  );

  $allResources = ob_get_clean();

  // If there were no resources to optimize
  if ('' === $allResources)
    return null;

  $resourceFolderPath = CACHE_PATH . $assetType . DIR_SEPARATOR;
  $pathAndFile = $resourceFolderPath . $shaName;

  if ($assetType === 'js')
    $pathAndFile .= '_';

  if (!file_exists($resourceFolderPath))
    mkdir($resourceFolderPath, 0755, true);

  file_put_contents($pathAndFile, $allResources);

  return $pathAndFile;
}

/**
 * Loads css or js resources
 *
 * @param string  $key          app_js, module_css kind of ...
 */
function loadResource(array $resources, array $chunks, string $key, string $bundlePath, ?string $resourcePath = null)
: void
{
  // If this kind of resource does not exist, we leave
  if (!isset($resources[$key]))
    return;

  $resourceType = substr(strrchr($key, '_'), 1);
  $resourcePath = $bundlePath . ($resourcePath ?? '') . 'resources/' . $resourceType . DIR_SEPARATOR;

  foreach ($resources[$key] as $resourceKey => $resource)
  {
    $resourceName = is_array($resource) ? $resourceKey : $resource;

    ob_start();

    if (!str_contains($resourceName, 'http'))
    {
      $finalPath = $resourcePath . $resourceName . '.' . $resourceType;

      if (file_exists($finalPath))
        echo file_get_contents($finalPath);
      else
      {
        ob_end_clean();
        return;
      }
    }
    else
    {
      $curlHandle = curl_init();
      curl_setopt($curlHandle, CURLOPT_URL, $resourceName . '.' . $resourceType);
      curl_setopt($curlHandle, CURLOPT_HEADER, false);
      curl_exec($curlHandle);
      curl_close($curlHandle);
    }

    $content = ob_get_clean();

    if ($resourceType === 'css')
    {
      $content = str_replace(
        ['  ', PHP_EOL, ' :', ': ', ', ', ' {', '{ ','; '],
        [' ', '', ':', ':', ',', '{', '{', ';'],
        $content
      );
    }

    echo $content;
    /** Workaround for Google Closure Compiler, version 'v20170218' built on 2017-02-23 11:19, that do not put a line
     *  feed after source map declaration like
     *  //# sourceMappingURL=modules.js.map
     *  So the last letter is 'p' and not a line feed.
     *  Then we have to put ourselves the line feed !
     **/

    if ($content[-1] === 'p')
      echo PHP_EOL;
  }
}

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws JsonException|OtraException
 * @return void
 */
function genAssets(array $argumentsVector) : void
{
  if (!file_exists(BUNDLES_PATH))
  {
    echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  require_once BASE_PATH . 'config/AllConfig.php';
  // require_once needed 'cause of the case of 'deploy' task that already launched the routes.
  require_once BASE_PATH . 'config/Routes.php';
  require CORE_PATH . 'tools/compression.php';

  define(
    __NAMESPACE__ . '\\JS_LEVEL_COMPILATION',
    [
      'WHITESPACE_ONLY',
      'SIMPLE_OPTIMIZATIONS',
      'ADVANCED_OPTIMIZATIONS'
    ][isset($argumentsVector[3]) ? (int) $argumentsVector[3] : 1]);

  $routes = Routes::$allRoutes;

  // Checking the mask parameter
  if (isset($argumentsVector[GEN_ASSETS_ARG_ASSETS_MASK]) && !is_numeric($argumentsVector[GEN_ASSETS_ARG_ASSETS_MASK]))
  {
    echo CLI_ERROR, 'This not a valid mask ! It must be between ', GEN_ASSETS_MASK_TEMPLATE, ' and ', GEN_ASSETS_MASK_TOTAL,
    '.', END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }

  define(
    __NAMESPACE__ . '\\ASSETS_MASK',
    (isset($argumentsVector[GEN_ASSETS_ARG_ASSETS_MASK])) ? $argumentsVector[GEN_ASSETS_ARG_ASSETS_MASK] + 0 : 31
  ); // 31 = default to all assets

  define('GEN_ASSETS_TEMPLATE', ASSETS_MASK & GEN_ASSETS_MASK_TEMPLATE);
  define('GEN_ASSETS_CSS', (ASSETS_MASK & GEN_ASSETS_MASK_CSS) >> 1);
  define('GEN_ASSETS_JS', (ASSETS_MASK & GEN_ASSETS_MASK_JS) >> 2);
  define('GEN_ASSETS_MANIFEST', (ASSETS_MASK & GEN_ASSETS_MASK_MANIFEST) >> 3);
  define('GEN_ASSETS_SVG', (ASSETS_MASK & GEN_ASSETS_MASK_SVG) >> 4);

  // If we only need the manifest or the SVGs, skips the assets' generation loop
  if (
    !in_array(
      ASSETS_MASK,
      [GEN_ASSETS_MASK_MANIFEST, GEN_ASSETS_MASK_SVG, GEN_ASSETS_MASK_MANIFEST | GEN_ASSETS_MASK_SVG]
    )
  )
  {
    // If we ask just for only one route
    if (isset($argumentsVector[GEN_ASSETS_ARG_ROUTE]))
    {
      $theRoute = $argumentsVector[GEN_ASSETS_ARG_ROUTE];

      if (!isset($routes[$theRoute]))
      {
        echo PHP_EOL, CLI_WARNING, 'This route does not exist !', END_COLOR, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }

      echo 'Cleaning the resources cache...';

      $routes = [$theRoute => $routes[$theRoute]];

      /***************** CLEANING THE FILES *******************
       ******* specific to the route passed in parameter ******/
      $shaName = sha1('ca' . $theRoute . VERSION . 'che');

      if (GEN_ASSETS_TEMPLATE !== 0)
        unlinkResourceFile('tpl/', $shaName);

      if (GEN_ASSETS_CSS !== 0)
        unlinkResourceFile('css/', $shaName);

      if (GEN_ASSETS_JS !== 0)
        unlinkResourceFile('js/', $shaName);
    } else
    {
      echo PHP_EOL, 'Cleaning the resources cache...';

      if (GEN_ASSETS_TEMPLATE !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'tpl/*'));

      if (GEN_ASSETS_CSS !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'css/*'));

      if (GEN_ASSETS_JS !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'js/*'));
    }

    echo CLI_SUCCESS, ' OK', END_COLOR, PHP_EOL;

    /*************** PROCESSING THE ROUTES ************/

    $cptRoutes = count($routes);
    $routePlural = '';

    if ($cptRoutes > 1)
      $routePlural = 's';

    echo $cptRoutes, ' route', $routePlural, ' to process. Processing the route', $routePlural, ' ...', PHP_EOL,
    PHP_EOL;

    require_once CONSOLE_PATH . 'deployment/googleClosureCompile.php';
    require CORE_PATH . 'tools/cli.php';

    foreach($routes as $routeName => $route)
    {
      // Showing the route name
      echo CLI_INFO_HIGHLIGHT, str_pad($routeName, 25), CLI_GRAY;

      $shaName = sha1('ca' . $routeName . VERSION . 'che');

      if (!isset($route['resources']))
      {
        echo status('Nothing to do', OTRA_CLI_INFO_STRING),
          ' =>', CLI_SUCCESS, ' OK', END_COLOR, '[', CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;
        continue;
      }

      $resources = $route['resources'];
      $chunks = $route['chunks'];

      // the bundle is generally not used in the OTRA exception route
      if (!isset($chunks[Routes::ROUTES_CHUNKS_BUNDLE]))
      {
        echo ' [NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]', '[',
        CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;
        continue;
      }

      $bundlePath = BASE_PATH . 'bundles/' . $chunks[Routes::ROUTES_CHUNKS_BUNDLE] . DIR_SEPARATOR;
      $noErrors = true;

      /***** CSS - GENERATES THE GZIPPED CSS FILES (IF ASKED AND IF NEEDED TO) *****/
      if (GEN_ASSETS_CSS !== 0)
      {
        if (str_contains(implode(array_keys($resources)), 'css'))
        {
          $pathAndFile = loadAndSaveResources($resources, $chunks, 'css', $bundlePath, $shaName);

          if ($pathAndFile !== null)
          {
            gzCompressFile($pathAndFile, null, GZIP_COMPRESSION_LEVEL);
            echo status('SCREEN CSS');
          }
          else
            echo status('NO SCREEN CSS', OTRA_CLI_INFO_STRING);

          ob_start();
          loadResource(
            $resources,
            $chunks,
            'print_css',
            $bundlePath . $chunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR
          );
          $printCss = ob_get_clean();

          if ($printCss === '')
            echo status('NO PRINT CSS', 'CLI_ERROR');
          else
          {
            $resourceFolderPath = CACHE_PATH . 'css/';
            $pathAndFile = $resourceFolderPath . 'print_' . $shaName;

            if (!file_exists($resourceFolderPath))
              mkdir($resourceFolderPath, 0755, true);

            file_put_contents($pathAndFile, $printCss);
            gzCompressFile($pathAndFile, null, GZIP_COMPRESSION_LEVEL);
            echo status('PRINT CSS');
          }
        }
        else
          echo status('NO CSS', OTRA_CLI_INFO_STRING);
      }

      /***** JS - GENERATES THE GZIPPED JS FILES (IF ASKED AND IF NEEDED TO) *****/
      if (GEN_ASSETS_JS !== 0)
      {
        if (str_contains(implode(array_keys($resources)), 'js'))
        {
          $pathAndFile = loadAndSaveResources($resources, $chunks, 'js', $bundlePath, $shaName);

          if ($pathAndFile === null)
          {
            echo CLI_ERROR, 'You are trying to generate empty assets for ', CLI_INFO_HIGHLIGHT, $routeName, CLI_ERROR,
              '!' . END_COLOR . PHP_EOL;
            continue;
          }

          $jsFileOut = mb_substr($pathAndFile, 0, -1);
          googleClosureCompile(
            0,
            json_decode(
              file_get_contents(BASE_PATH . OTRA_LABEL_TSCONFIG_JSON),
              true,
              512,
              JSON_THROW_ON_ERROR
            ),
            $pathAndFile,
            $jsFileOut,
            JS_LEVEL_COMPILATION
          );

          gzCompressFile($jsFileOut, $jsFileOut . '.gz', GZIP_COMPRESSION_LEVEL);
          echo status('JS');
        }
        else
          echo status('NO JS', OTRA_CLI_INFO_STRING);
      }

      /***** TEMPLATE - GENERATES THE GZIPPED TEMPLATE FILES IF THE ROUTE IS STATIC *****/
      if (GEN_ASSETS_TEMPLATE !== 0)
      {
        if (!isset($resources['template']) || !$resources['template'])
          echo status('NO TEMPLATE', OTRA_CLI_INFO_STRING); // no static template
        else
        {
          $staticTemplateBasePath = (isset($resources['noNonces']) && $resources['noNonces'])
            ? BASE_PATH . 'web/'
            : CACHE_PATH;

          // Generates the gzipped template files
          passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genAssets/genTemplate.php" "' .
            $staticTemplateBasePath . '" "' .
            $routeName . '" ' .
            $shaName . ' "' .
            'bundles\\' . Routes::$allRoutes[$routeName]['chunks'][Routes::ROUTES_CHUNKS_BUNDLE] . '\\Init::Init"'
          );

          if (file_exists(CACHE_PATH . 'tpl/' . $shaName . '.gz'))
            echo status('TEMPLATE');
          else
          {
            echo status('TEMPLATE', 'CLI_ERROR');
            $noErrors = false;
          }
        }
      }

      echo ' => ', $noErrors ? CLI_SUCCESS . 'OK' . END_COLOR : CLI_ERROR . 'ERROR' . END_COLOR, '[',
      CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;
    }
  }

  if (GEN_ASSETS_MANIFEST !== 0)
  {
    $jsonManifestPath = BASE_PATH . 'web/devManifest.json';

    if (!file_exists($jsonManifestPath))
      echo CLI_ERROR, 'The JSON manifest file ', CLI_WARNING, $jsonManifestPath, CLI_ERROR , ' to optimize does not exist.',
      END_COLOR, PHP_EOL;
    else
    {
      $message = 'Generation of the gzipped JSON manifest';
      echo $message . '...', PHP_EOL;

      $contents = file_get_contents($jsonManifestPath);
      $contents = preg_replace('@([{,:])\s+(")@', '$1$2', $contents);
      $contents = preg_replace('@([\[,])\s+(\{)@', '$1$2', $contents);
      $contents = preg_replace('@(")\s+(\})@', '$1$2', $contents);
      $contents = preg_replace('@(\})\s+(\])@', '$1$2', $contents);
      $generatedJsonManifestPath = BASE_PATH . 'web/manifest';

      file_put_contents($generatedJsonManifestPath, $contents);
      gzCompressFile($generatedJsonManifestPath, $generatedJsonManifestPath . '.gz', GZIP_COMPRESSION_LEVEL);
      echo "\033[1A\033[" . strlen($message) . "C", CLI_SUCCESS . '  ✔  ' . END_COLOR . PHP_EOL;
    }
  }

  if (GEN_ASSETS_SVG !== 0)
  {
    define(__NAMESPACE__ . '\\FOLDER_TO_CHECK_FOR_SVGS', BASE_PATH . 'web/images');
    echo 'Checking for uncompressed SVGs in the folder ', CLI_INFO_HIGHLIGHT, FOLDER_TO_CHECK_FOR_SVGS, END_COLOR, ' ...',
    PHP_EOL;

    // Searches in the 'web/images' folder for SVGs
    if (file_exists(FOLDER_TO_CHECK_FOR_SVGS))
    {
      $dir_iterator = new RecursiveDirectoryIterator(FOLDER_TO_CHECK_FOR_SVGS, FilesystemIterator::SKIP_DOTS);

      $iterator = new RecursiveIteratorIterator($dir_iterator);

      /** @var SplFileInfo $entry */
      foreach($iterator as $entry)
      {
        $extension = $entry->getExtension();

        if ($extension !== 'svg' || $entry->isDir())
          continue;

        $realPath = $entry->getRealPath();

        if (!gzCompressFile($realPath, $realPath . '.gz', GZIP_COMPRESSION_LEVEL))
        {
          echo CLI_ERROR, 'There was an error during the gzip compression of the file ', CLI_INFO_HIGHLIGHT,
          mb_substr($realPath, mb_strlen(BASE_PATH)), '.', END_COLOR, PHP_EOL;
        } else
        {
          echo 'The file ', CLI_INFO_HIGHLIGHT, mb_substr($realPath, mb_strlen(BASE_PATH)), END_COLOR,
          ' has been compressed successfully.', END_COLOR, PHP_EOL;
        }
      }

      echo 'All SVGs are compressed.', PHP_EOL;
    }
    else
      echo CLI_WARNING, 'There is no folder ', CLI_INFO_HIGHLIGHT, FOLDER_TO_CHECK_FOR_SVGS, CLI_WARNING, '.', END_COLOR,
      PHP_EOL;
  }
}
