<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use config\Routes;
use JetBrains\PhpStorm\Pure;
use otra\OtraException;

require_once BASE_PATH . 'config/AllConfig.php';
// require_once needed 'cause of the case of 'deploy' task that already launched the routes.
require_once BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/compression.php';

define('GEN_ASSETS_ARG_ASSETS_MASK', 2);
define(
  'JS_LEVEL_COMPILATION',
  [
    'WHITESPACE_ONLY',
    'SIMPLE_OPTIMIZATIONS',
    'ADVANCED_OPTIMIZATIONS'
  ][isset($argv[3]) ? intval($argv[3]) : 1]);
define('GEN_ASSETS_ARG_ROUTE', 4);
define('GZIP_COMPRESSION_LEVEL', 9);

define('GEN_ASSETS_MASK_TEMPLATE', 1);
define('GEN_ASSETS_MASK_CSS', 2);
define('GEN_ASSETS_MASK_JS', 4);
define('GEN_ASSETS_MASK_MANIFEST', 8);
define('GEN_ASSETS_MASK_SVG', 16);
define('GEN_ASSETS_MASK_TOTAL', 31);

define('ROUTES_CHUNKS_BUNDLE', 1);
define('ROUTES_CHUNKS_MODULE', 2);

define('OTRA_UNLINK_CALLBACK', 'unlink');
define('OTRA_CLI_CYAN_STRING', 'CLI_CYAN');

$routes = Routes::$allRoutes;

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

if (isset($argv[GEN_ASSETS_ARG_ASSETS_MASK]) && !is_numeric($argv[GEN_ASSETS_ARG_ASSETS_MASK]))
{
  echo CLI_RED, 'This not a valid mask ! It must be between ', GEN_ASSETS_MASK_TEMPLATE, ' and ', GEN_ASSETS_MASK_TOTAL,
    '.', END_COLOR;
  throw new OtraException('', 1, '', NULL, [], true);
}

define(
  'ASSETS_MASK',
  (isset($argv[GEN_ASSETS_ARG_ASSETS_MASK])) ? $argv[GEN_ASSETS_ARG_ASSETS_MASK] + 0 : 31
); // 31 = default to all assets

define('GEN_ASSETS_TEMPLATE', ASSETS_MASK & GEN_ASSETS_MASK_TEMPLATE);
define('GEN_ASSETS_CSS', (ASSETS_MASK & GEN_ASSETS_MASK_CSS) >> 1);
define('GEN_ASSETS_JS', (ASSETS_MASK & GEN_ASSETS_MASK_JS) >> 2);
define('GEN_ASSETS_MANIFEST', (ASSETS_MASK & GEN_ASSETS_MASK_MANIFEST) >> 3);
define('GEN_ASSETS_SVG', (ASSETS_MASK & GEN_ASSETS_MASK_SVG) >> 4);

// If we only need the manifest or the SVGs, skips the assets generation loop
if (
  !in_array(
    ASSETS_MASK,
    [GEN_ASSETS_MASK_MANIFEST, GEN_ASSETS_MASK_SVG, GEN_ASSETS_MASK_MANIFEST | GEN_ASSETS_MASK_SVG]
  )
)
{
  // If we ask just for only one route
  if (isset($argv[GEN_ASSETS_ARG_ROUTE]))
  {
    $theRoute = $argv[GEN_ASSETS_ARG_ROUTE];

    if (!isset($routes[$theRoute]))
    {
      echo PHP_EOL, CLI_YELLOW, 'This route does not exist !', END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', NULL, [], true);
    }

    echo 'Cleaning the resources cache...';

    $routes = [$theRoute => $routes[$theRoute]];

    /***************** CLEANING THE FILES *******************
     ******* specific to the route passed in parameter ******/
    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    if (GEN_ASSETS_TEMPLATE)
      unlinkResourceFile('tpl/', $shaName);

    if (GEN_ASSETS_CSS)
      unlinkResourceFile('css/', $shaName);

    if (GEN_ASSETS_JS)
      unlinkResourceFile('js/', $shaName);
  } else
  {
    echo PHP_EOL, 'Cleaning the resources cache...';

    if (GEN_ASSETS_TEMPLATE)
      array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'tpl/*'));

    if (GEN_ASSETS_CSS)
      array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'css/*'));

    if (GEN_ASSETS_JS)
      array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'js/*'));
  }

  echo CLI_LIGHT_GREEN, ' OK', END_COLOR, PHP_EOL;

  /*************** PROCESSING THE ROUTES ************/

  $cptRoutes = count($routes);
  $routePlural = '';

  if ($cptRoutes > 1)
    $routePlural = 's';

  echo $cptRoutes, ' route', $routePlural, ' to process. Processing the route', $routePlural, ' ... ', PHP_EOL,
    PHP_EOL;

  foreach($routes as $routeName => $route)
  {
    // Showing the route name
    echo CLI_LIGHT_CYAN, str_pad($routeName, 25), CLI_LIGHT_GRAY;

    $shaName = sha1('ca' . $routeName . VERSION . 'che');

    if (!isset($route['resources']))
    {
      echo status('Nothing to do', OTRA_CLI_CYAN_STRING), ' =>', CLI_LIGHT_GREEN, ' OK', END_COLOR, '[',
      CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
      continue;
    }

    $resources = $route['resources'];
    $chunks = $route['chunks'];

    // TODO suppress this block and do the appropriate fixes
    if (!isset($chunks[ROUTES_CHUNKS_BUNDLE]))
    {
      echo ' [NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]', '[',
      CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
      continue;
    }

    $bundlePath = BASE_PATH . 'bundles/' . $chunks[ROUTES_CHUNKS_BUNDLE] . '/';
    $noErrors = true;

    /***** CSS - GENERATES THE GZIPPED CSS FILES (IF ASKED AND IF NEEDED TO) *****/
    if (GEN_ASSETS_CSS)
    {
      if (str_contains(implode(array_keys($resources)), 'css'))
      {
        $pathAndFile = loadAndSaveResources($resources, $chunks, 'css', $bundlePath, $shaName);

        if ($pathAndFile !== null)
        {
          gzCompressFile($pathAndFile, null, GZIP_COMPRESSION_LEVEL);
          echo status('SCREEN CSS');
        } else
          echo status('NO SCREEN CSS', OTRA_CLI_CYAN_STRING);

        ob_start();
        loadResource($resources, $chunks, 'print_css', $bundlePath);
        $printCss = ob_get_clean();

        if ($printCss === '')
          echo status('NO PRINT CSS', 'CLI_RED');
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
      } else
        echo status('NO CSS', OTRA_CLI_CYAN_STRING);


    }

    /***** JS - GENERATES THE GZIPPED JS FILES (IF ASKED AND IF NEEDED TO) *****/
    if (GEN_ASSETS_JS)
    {
      if (str_contains(implode(array_keys($resources)), 'js'))
      {
        $pathAndFile = loadAndSaveResources($resources, $chunks, 'js', $bundlePath, $shaName);

        // Linux or Windows ? We have java or jamvm ?
        if (!str_contains(php_uname('s'), 'Windows')) // LINUX CASE
        {
          if (!empty(exec('which java'))) // JAVA CASE
          {
            // JAVA CASE
            /** TODO Find a way to store the logs (and then remove -W QUIET) */
            exec('java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar "' . CONSOLE_PATH . 'deployment/compiler.jar" --logging_level FINEST -W QUIET --rewrite_polyfills=false --js "' .
              $pathAndFile . '" --js_output_file "' . $pathAndFile . '" --language_in=ECMASCRIPT6_STRICT --language_out=ES5_STRICT -O ' . JS_LEVEL_COMPILATION);
            gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
          } else
          {
            // JAMVM CASE
            exec('jamvm -Xmx32m -jar ../src/yuicompressor-2.4.8.jar "' . $pathAndFile . '" -o "' . $pathAndFile . '" --type js;');
            gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
          }
        } elseif (empty(exec('where java'))) // WINDOWS AND JAMVM CASE
        {
          exec('jamvm -Xmx32m -jar ../src/yuicompressor-2.4.8.jar "' . $pathAndFile . '" -o "' . $pathAndFile . '" --type js');
          gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
        } else
        {
          // JAVA CASE
          /** TODO Find a way to store the logs (and then remove -W QUIET) */
          exec('java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar "' . CONSOLE_PATH . 'deployment/compiler.jar" --logging_level FINEST -W QUIET --rewrite_polyfills=false --js "' .
            $pathAndFile . '" --js_output_file "' . $pathAndFile . '" --language_in=ECMASCRIPT6_STRICT --language_out=ES5_STRICT -O ' . JS_LEVEL_COMPILATION);
          gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
        }

        echo status('JS');
      } else
        echo status('NO JS', OTRA_CLI_CYAN_STRING);
    }

    /***** TEMPLATE - GENERATES THE GZIPPED TEMPLATE FILES IF THE ROUTE IS STATIC *****/
    if (GEN_ASSETS_TEMPLATE)
    {
      if (!isset($resources['template']))
        echo status('NO TEMPLATE', OTRA_CLI_CYAN_STRING);
      else
      {
        // Generates the gzipped template files
        passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genAssets/genAsset.php" "' .
          CACHE_PATH . '" "' .
          $routeName . '" ' .
          $shaName . ' "' .
          'bundles\\' . Routes::$allRoutes[$routeName]['chunks'][1] . '\\Init::Init"'
        );

        if (file_exists(CACHE_PATH . 'tpl/' . $shaName . '.gz'))
          echo status('TEMPLATE');
        else
        {
          echo status('TEMPLATE', 'CLI_RED');
          $noErrors = false;
        }
      }
    }

    echo ' => ', $noErrors ? CLI_LIGHT_GREEN . 'OK' . END_COLOR : CLI_RED . 'ERROR' . END_COLOR, '[',
    CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
  }
}

if (GEN_ASSETS_MANIFEST)
{
  $jsonManifestPath = BASE_PATH . 'web/devManifest.json';

  if (!file_exists($jsonManifestPath))
    echo CLI_RED, 'The JSON manifest file ', CLI_YELLOW, $jsonManifestPath, CLI_RED , ' to optimize does not exist.',
      END_COLOR, PHP_EOL;
  else
  {
    $message = 'Generation of the gzipped JSON manifest';
    echo $message . '...', PHP_EOL;

    $contents = file_get_contents($jsonManifestPath);
    $contents = preg_replace('@([{,:])\s{1,}(")@', '$1$2', $contents);
    $contents = preg_replace('@([\[,])\s{1,}(\{)@', '$1$2', $contents);
    $contents = preg_replace('@(")\s{1,}(\})@', '$1$2', $contents);
    $contents = preg_replace('@(\})\s{1,}(\])@', '$1$2', $contents);
    $generatedJsonManifestPath = BASE_PATH . 'web/manifest';

    file_put_contents($generatedJsonManifestPath, $contents);
    gzCompressFile($generatedJsonManifestPath, $generatedJsonManifestPath . '.gz', GZIP_COMPRESSION_LEVEL);
    echo "\033[1A\033[" . strlen($message) . "C", CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL;
  }
}

if (GEN_ASSETS_SVG)
{
  define('FOLDER_TO_CHECK_FOR_SVGS', BASE_PATH . 'web/images');
  echo 'Checking for uncompressed SVGs in the folder ', CLI_LIGHT_CYAN, FOLDER_TO_CHECK_FOR_SVGS, END_COLOR, ' ...',
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
        echo CLI_RED, 'There was an error during the gzip compression of the file ', CLI_LIGHT_CYAN,
        mb_substr($realPath, strlen(BASE_PATH)), '.', END_COLOR, PHP_EOL;
      } else
      {
        echo 'The file ', CLI_LIGHT_CYAN, mb_substr($realPath, strlen(BASE_PATH)), END_COLOR,
        ' has been compressed successfully.', END_COLOR, PHP_EOL;
      }
    }

    echo 'All SVGs are compressed.', PHP_EOL;
  } else
    echo CLI_YELLOW, 'There is no folder ', CLI_LIGHT_CYAN, FOLDER_TO_CHECK_FOR_SVGS, CLI_YELLOW, '.', END_COLOR,
      PHP_EOL;
}

/**
 *
 * @param string $status
 * @param string $color
 *
 * @return string
 */
#[Pure] function status(string $status, string $color = 'CLI_LIGHT_GREEN') : string
{
  return ' [' . constant($color) . $status . CLI_LIGHT_GRAY. ']';
}

/**
 * @param array  $resources
 * @param array  $routeChunks
 * @param string $type       'css' or 'js'
 * @param string $bundlePath
 * @param string $shaName
 *
 * @return null|string Return the path of the 'macro' resource file
 */
function loadAndSaveResources(
  array $resources,
  array $routeChunks,
  string $type,
  string $bundlePath,
  string $shaName
) : ?string
{
  ob_start();
  loadResource($resources, $routeChunks, 'first_' . $type, $bundlePath);
  loadResource($resources, $routeChunks, 'bundle_' . $type, $bundlePath, '');
  loadResource($resources, $routeChunks, 'module_' . $type, $bundlePath . $routeChunks[2] . '/');
  loadResource($resources, $routeChunks, '_' . $type, $bundlePath);

  $allResources = ob_get_clean();

  // If there was no resources to optimize
  if ('' === $allResources)
    return null;

  $resourceFolderPath = CACHE_PATH . $type . '/';
  $pathAndFile = $resourceFolderPath . $shaName;

  if (!file_exists($resourceFolderPath))
    mkdir($resourceFolderPath, 0755, true);

  file_put_contents($pathAndFile, $allResources);

  return $pathAndFile;
}

/**
 * Loads css or js resources
 *
 * @param array  $resources
 * @param array  $chunks
 * @param string $key first_js, module_css kind of ...
 * @param string $bundlePath
 * @param ?bool  $resourcePath
 */
function loadResource(array $resources, array $chunks, string $key, string $bundlePath, ?string $resourcePath = null)
: void
{
  // If this kind of resource does not exist, we leave
  if (!isset($resources[$key]))
    return;

  $resourceType = substr(strrchr($key, '_'), 1);
  $resourcePath = $bundlePath .
    (null === $resourcePath ? $chunks[ROUTES_CHUNKS_MODULE] . '/' : $resourcePath) .
    'resources/' . $resourceType . '/';

  foreach ($resources[$key] as $resource)
  {
    ob_start();

    if (!str_contains($resource, 'http'))
    {
      $finalPath = $resourcePath . $resource . '.' . $resourceType;
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
      curl_setopt($curlHandle, CURLOPT_URL, $resource . '.' . $resourceType);
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
    /** Workaround for google closure compiler, version 'v20170218' built on 2017-02-23 11:19, that do not put a line
     *  feed after source map declaration like
     *  //# sourceMappingURL=modules.js.map
     *  So the last letter is 'p' and not a line feed.
     *  Then we have to put ourselves the line feed !
     **/

    if ($content[-1] === 'p')
      echo PHP_EOL;
  }
}
