<?php
require_once BASE_PATH . 'config/AllConfig.php';
// require_once needed 'cause of the case of 'deploy' task that already launched the routes.
require_once BASE_PATH . 'config/Routes.php';
require CORE_PATH . 'tools/compression.php';

define('ASSET_MASK', 2);
define('JS_LEVEL_COMPILATION', ['WHITESPACE_ONLY', 'SIMPLE_OPTIMIZATIONS', 'ADVANCED_OPTIMIZATIONS'][$argv[3] ?? 1]);
define('ROUTE', 4);
define('GZIP_COMPRESSION_LEVEL', 9);

$routes = \config\Routes::$_;

/**
 * @param string $folder  The resource folder name
 * @param string $shaName The route's name encoded in sha1
 */
function unlinkResourceFile(string $folder, string $shaName)
{
  $file = CACHE_PATH . $folder . $shaName . '.gz';

  if (true === file_exists($file))
    unlink($file);
}

if (true === isset($argv[ASSET_MASK]) && false === is_numeric($argv[ASSET_MASK]))
{
  echo CLI_RED, 'This not a valid mask ! It must be between 1 and 7.', END_COLOR;
  exit(1);
}

$mask = (true === isset($argv[ASSET_MASK])) ? $argv[ASSET_MASK] + 0 : 15; // 15 = default to all assets

// If we only need the manifest, skips the assets generation loop
if ($mask !== 8)
{
  // If we ask just for only one route
  if (true === isset($argv[ROUTE]))
  {
    $theRoute = $argv[ROUTE];

    if (false === isset($routes[$theRoute]))
    {
      echo PHP_EOL, CLI_YELLOW, 'This route does not exist !', END_COLOR, PHP_EOL;
      exit(1);
    }

    echo 'Cleaning the resources cache...';

    $routes = array($theRoute => $routes[$theRoute]);

    /***************** CLEANING THE FILES *******************
     ******* specific to the route passed in parameter ******/

    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    if ($mask & 1)
      unlinkResourceFile('tpl/', $shaName);

    if (($mask & 2) >> 1)
      unlinkResourceFile('css/', $shaName);

    if (($mask & 4) >> 2)
      unlinkResourceFile('js/', $shaName);
  } else
  {
    echo PHP_EOL, 'Cleaning the resources cache...';

    if ($mask & 1)
      array_map('unlink', glob(CACHE_PATH . 'tpl/*'));

    if (($mask & 2) >> 1)
      array_map('unlink', glob(CACHE_PATH . 'css/*'));

    if (($mask & 4) >> 2)
      array_map('unlink', glob(CACHE_PATH . 'js/*'));
  }

  echo CLI_LIGHT_GREEN, ' OK', END_COLOR, PHP_EOL;

  /*************** PROCESSING THE ROUTES ************/

  $cptRoutes = count($routes);

  echo $cptRoutes, ' route(s) to process. Processing the route(s) ... ', PHP_EOL, PHP_EOL;

  for($i = 0; $i < $cptRoutes; ++$i)
  {
    $route = current($routes);
    $routeName = key($routes);
    next($routes);

    // Showing the route name
    echo CLI_LIGHT_CYAN, str_pad($routeName, 25, ' '), CLI_LIGHT_GRAY;

    $shaName = sha1('ca' . $routeName . VERSION . 'che');

    if (false === isset($route['resources']))
    {
      echo status('Nothing to do', 'CLI_CYAN'), ' =>', CLI_LIGHT_GREEN, ' OK', END_COLOR, '[',
        CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
      continue;
    }

    $resources = $route['resources'];
    $chunks = $route['chunks'];

    // TODO suppress this block and do the appropriate fixes
    if (false === isset($chunks[1]))
    {
      echo ' [NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]', '[',
      CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
      continue;
    }

    $bundlePath = (true === isset($chunks[1])
      ? BASE_PATH . 'bundles/' . $chunks[1] . '/'
      : CORE_PATH
    );

    $noErrors = true;

    /***** CSS - GENERATES THE GZIPPED CSS FILES (IF ASKED AND IF NEEDED TO) *****/
    if (($mask & 2) >> 1)
    {
      if (strpos(implode(array_keys($resources)), 'css') !== false)
      {
        $pathAndFile = loadAndSaveResources($resources, $chunks, 'css', $bundlePath, $shaName);

        if ($pathAndFile !== null)
        {
          gzCompressFile($pathAndFile, null, GZIP_COMPRESSION_LEVEL);
          echo status('CSS');
        } else
          echo status('NO CSS', 'CLI_CYAN');
      } else
        echo status('NO CSS', 'CLI_CYAN');
    }

    /***** JS - GENERATES THE GZIPPED JS FILES (IF ASKED AND IF NEEDED TO) *****/
    if (($mask & 4) >> 2)
    {
      if (strpos(implode(array_keys($resources)), 'js') !== false)
      {
        $pathAndFile = loadAndSaveResources($resources, $chunks, 'js', $bundlePath, $shaName);

        // Linux or Windows ? We have java or jamvm ?
        if (false === strpos(php_uname('s'), 'Windows')) // LINUX CASE
        {
          if (true !== empty(exec('which java'))) // JAVA CASE
          {
            // JAVA CASE
            /** TODO Find a way to store the logs (and then remove -W QUIET) */
            exec('java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar "' . CONSOLE_PATH . 'deployment/compiler.jar" --logging_level FINEST -W QUIET --rewrite_polyfills=false --js "' .
              $pathAndFile . '" --js_output_file "' . $pathAndFile . '" --language_in=ECMASCRIPT6_STRICT --language_out=ES5_STRICT -O ' . JS_LEVEL_COMPILATION);
            gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
          } else {
            // JAMVM CASE
            exec('jamvm -Xmx32m -jar ../src/yuicompressor-2.4.8.jar "' . $pathAndFile . '" -o "' . $pathAndFile . '" --type js;');
            gzCompressFile($pathAndFile, $pathAndFile . '.gz', GZIP_COMPRESSION_LEVEL);
          }
        } elseif (true === empty(exec('where java'))) // WINDOWS AND JAMVM CASE
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
        echo status('NO JS', 'CLI_CYAN');
    }

    /***** TEMPLATE - GENERATES THE GZIPPED TEMPLATE FILES IF THE ROUTE IS STATIC *****/
    if ($mask & 1)
    {
      if (false === isset($resources['template']))
        echo status('No TEMPLATE', 'CLI_CYAN');
      else
      {
        // Generates the gzipped template files
        passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genAssets/genAsset.php" "' .
          CACHE_PATH . '" "' .
          $routeName . '" ' .
          $shaName . ' "' .
          'bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init"'
        );

        if (file_exists(CACHE_PATH . 'tpl/' . $shaName . '.gz'))
          echo status('TEMPLATE');
        else
        {
          status('TEMPLATE', 'CLI_RED');
          $noErrors = false;
        }
      }
    }

    echo ' => ', $noErrors === true ? CLI_LIGHT_GREEN . 'OK' . END_COLOR : CLI_RED . 'ERROR' . END_COLOR, '[',
    CLI_CYAN, $shaName, END_COLOR, ']', PHP_EOL;
  }
}

if (($mask & 8) >> 3)
{
  $jsonManifestPath = BASE_PATH . 'web/devManifest.json';

  if (!file_exists($jsonManifestPath))
  {
    echo CLI_RED, 'The JSON manifest file ', CLI_YELLOW, $jsonManifestPath, CLI_RED , ' to optimize does not exist.', END_COLOR,
    PHP_EOL;
    throw new \otra\OtraException('', 1, '', NULL, [], true);
  }

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

/**
 *
 * @param string $status
 * @param string $color
 *
 * @return string
 */
function status(string $status, string $color = 'CLI_LIGHT_GREEN') : string { return ' [' . constant($color) . $status . CLI_LIGHT_GRAY. ']'; }

/**
 * @param array  $resources
 * @param array  $routeInfos
 * @param string $type       'css' or 'js'
 * @param string $bundlePath
 * @param string $shaName
 *
 * @return null|string Return the path of the 'macro' resource file
 */
function loadAndSaveResources(array $resources, array $routeInfos, string $type, string $bundlePath, string $shaName)
: ?string
{
  ob_start();
  loadResource($resources, $routeInfos, 'first_' . $type, $bundlePath);
  loadResource($resources, $routeInfos, 'bundle_' . $type, $bundlePath, '');
  //loadResource($resources, $routeInfos, 'module_' . $type, $bundlePath, $routeInfos[2] . '/');
  loadResource($resources, $routeInfos, 'module_' . $type, $bundlePath . $routeInfos[2] . '/');
  loadResource($resources, $routeInfos, '_' . $type, $bundlePath);

  $allResources = ob_get_clean();

  // If there was no resources to optimize
  if ('' === $allResources)
    return null;

  $resourceFolderPath = CACHE_PATH . $type . '/';
  $pathAndFile = $resourceFolderPath . $shaName;

  if (file_exists($resourceFolderPath) === false)
    mkdir($resourceFolderPath, 0755, true);

  file_put_contents($pathAndFile, $allResources);

  return $pathAndFile;
}

/**
 * Loads css or js resources
 *
 * @param array       $resources
 * @param array       $chunks
 * @param string      $key        first_js, module_css kind of ...
 * @param string      $bundlePath
 * @param string|bool $path
 */
function loadResource(array $resources, array $chunks, string $key, string $bundlePath, $path = true)
{
  // If this kind of resource does not exist, we leave
  if (false === isset($resources[$key]))
    return;

  $type = substr(strrchr($key, '_'), 1);
  $path = $bundlePath . (true === $path ? $chunks[2] . '/' : $path) . 'resources/' . $type . '/';

  foreach ($resources[$key] as &$resource)
  {
    ob_start();

    if (false === strpos($resource, 'http'))
      echo file_get_contents($path . $resource . '.' . $type);
    else
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $resource . '.' . $type);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_exec($ch);
      curl_close($ch);
    }

    $content = ob_get_clean();

    if ($type === 'css')
    {
      $content = str_replace(
        ['  ', PHP_EOL, ' :', ': ', ', ', ' {', '{ ','; '],
        [' ', '', ':', ':', ',', '{', '{', ';'],
        $content
      );
    }

    echo $content;
    /** Workaround for google closure compiler, version 'v20170218' built on 2017-02-23 11:19, that do not put a line feed after source map declaration like
     *  //# sourceMappingURL=modules.js.map
     *  So the last letter is 'p' and not a line feed.
     *  Then we have to put ourselves the line feed !
     **/

    if ($content[-1] === 'p')
      echo PHP_EOL;
  }
}
