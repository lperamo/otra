<?
require_once BASE_PATH . '/config/AllConfig.php';
// require_once needed 'cause of the case of 'deploy' task that already launched the routes.
require_once BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/tools/Compression.php';
// require BASE_PATH . '/lib/packerjs/JavaScriptPacker.php';

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

if (true === isset($argv[2]) && false === is_numeric($argv[2]))
  dieC('red', 'This not a valid mask ! It must be between 1 and 7.');

// If we ask just for only one route
if (true === isset($argv[3]))
{
  $theRoute = $argv[3];

  if (false === isset($routes[$theRoute]))
    dieC('yellow', PHP_EOL . 'This route doesn\'t exist !' . PHP_EOL);

  echo 'Cleaning the resources cache...';
  $mask = (true === isset($argv[2])) ? $argv[2] + 0 : 7;

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
  $mask = (true === isset($argv[2])) ? $argv[2] + 0 : 7;

  if ($mask & 1)
    array_map('unlink', glob(CACHE_PATH . 'tpl/*'));

  if (($mask & 2) >> 1)
    array_map('unlink', glob(CACHE_PATH . 'css/*'));

  if (($mask & 4) >> 2)
    array_map('unlink', glob(CACHE_PATH . 'js/*'));
}

echo lightGreenText(' OK'), PHP_EOL;

/*************** PROCESSING THE ROUTES ************/

$cptRoutes = count($routes);

echo $cptRoutes, ' route(s) to process. Processing the route(s) ... ', PHP_EOL, PHP_EOL;

for($i = 0; $i < $cptRoutes; ++$i)
{
  $route = current($routes);
  $routeName = key($routes);
  next($routes);
  echo lightCyan(), str_pad($routeName, 25, ' '), lightGray();

  if (false === isset($route['resources']))
  {
    echo status('Nothing to do', 'cyan'), ' =>', lightGreenText(' OK'), PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  $chunks = $route['chunks'];
  $shaName = sha1('ca' . $routeName . VERSION . 'che');

  // TODO suppress this block and do the appropriate fixes
  if (false === isset($chunks[1]))
  {
    echo '[NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]', PHP_EOL;
    continue;
  }

  $bundlePath = (true === isset($chunks[1])
    ? BASE_PATH . '/bundles/' . $chunks[1] . '/'
    : CORE_PATH
  );

  /***** CSS - GENERATES THE GZIPPED CSS FILES (IF ASKED AND IF NEEDED TO) *****/
  if (($mask & 2) >> 1)
  {
    if (strpos(implode(array_keys($resources)), 'css') !== false)
    {
      gzCompressFile(loadAndSaveResources($resources, $chunks, 'css', $bundlePath, $shaName), null,9);
      echo status('CSS');
    } else
      echo status('NO CSS', 'cyan');
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
        if (true === empty(exec('which java'))) // JAMVM CASE
        {
          exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar "' . $pathAndFile . '" -o "' . $pathAndFile . '" --type js;');
          gzCompressFile($pathAndFile, $pathAndFile . '.gz', 9);
        }
      } else if (true === empty(exec('where java'))) // WINDOWS AND JAMVM CASE
      {
        exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar "' . $pathAndFile . '" -o "' . $pathAndFile . '" --type js');
        gzCompressFile($pathAndFile, $pathAndFile . '.gz', 9);
      } else
      {
        // JAVA CASE

        /** TODO Find a way to store the logs (and then remove -W QUIET), other thing interesting --compilation_level ADVANCED_OPTIMIZATIONS */
        exec('java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar "' . CORE_PATH . 'console/compiler.jar" --logging_level FINEST -W QUIET --rewrite_polyfills=false --js "' .
          $pathAndFile . '" --js_output_file "' . $pathAndFile . '" --language_in=ECMASCRIPT6_STRICT --language_out=ES5_STRICT');
        gzCompressFile($pathAndFile, $pathAndFile . '.gz', 9);
      }

      echo status('JS');
    } else
      echo status('NO JS', 'cyan');
  }

  /***** TEMPLATE - GENERATES THE GZIPPED TEMPLATE FILES IF THE ROUTE IS STATIC *****/
  if ($mask & 1)
  {
    if (false === isset($resources['template']))
      echo status('No TEMPLATE', 'cyan');
    else
    {
      // Generates the gzipped template files
      passthru(PHP_BINARY . ' "' . CORE_PATH . 'console/GenAsset.php" "' .
        CACHE_PATH . '" "' .
        $routeName . '" ' .
        $shaName . ' "' .
        'bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init"'
      );

      echo status('TEMPLATE');
    }
  }

  echo ' => ', lightGreenText('OK'), '[', cyanText($shaName), ']', PHP_EOL;
}

/**
 * @param string $status
 * @param string $color
 *
 * @return string
 */
function status(string $status, string $color = 'lightGreen') : string { return ' [' . $color() . $status . lightGray(). ']'; }

/**
 * Cleans the css (spaces and comments)
 *
 * @param $content string The css content to clean
 *
 * @return string The cleaned css
 */
function cleanCss(string $content) : string
{
  $content = preg_replace('@/\*.*?\*/@s', '', $content);
  $content = str_replace(["\r\n", "\r", "\n", "\t", '  '], '', $content);
  $content = str_replace(['{ ',' {'], '{', $content);
  $content = str_replace([' }','} '], '}', $content);
  $content = str_replace(['; ',' ;'], ';', $content);
  $content = str_replace([', ',' ,'], ',', $content);

  return str_replace(': ', ':', $content);
}

/**
 * @param array  $resources
 * @param array  $routeInfos
 * @param string $type       'css' or 'js'
 * @param string $bundlePath
 * @param string $shaName
 *
 * @return string Return the path of the 'macro' resource file
 */
function loadAndSaveResources(array $resources, array $routeInfos, string $type, string $bundlePath, string $shaName)
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
    return status('No ' . strtoupper($type), 'cyan');

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