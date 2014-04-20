<?
define('BASE_PATH', substr(__DIR__, 0, -16)); // Finit avec /
require BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/core/Router.php';
require_once BASE_PATH . '/config/All_Config.php';
require BASE_PATH . '/lib/packerjs/JavaScriptPacker.php';

$routes = \config\Routes::$_;

// If we ask just for only one route
if(isset($argv[3]))
{
  $theRoute = $argv[3];
  if(isset($routes[$theRoute])){
    echo PHP_EOL, 'Cleaning the resources cache...';
    $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

    $routes = array($theRoute => $routes[$theRoute]);
    // Cleaning the files specific to the route passed in parameter
    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    if($mask & 1){
      $file = CACHE_PATH . 'tpl/' . $shaName . '.html.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 2) >> 1)
    {
      $file = CACHE_PATH . 'css/' . $shaName . '.css.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 4) >> 2)
    {
      $file = CACHE_PATH . 'js/' . $shaName . '.js.gz';
      if(file_exists($file))
        unlink($file);
    }
    echo green(), ' OK', PHP_EOL, endColor();
  } else
    dieC('yellow', PHP_EOL . 'This route doesn\'t exist !' . PHP_EOL);
}else
{
  echo PHP_EOL, 'Cleaning the resources cache...';
  $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

  if($mask & 1)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/tpl/*'));

  if(($mask & 2) >> 1)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/css/*'));

  if(($mask & 4) >> 2)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/js/*'));

  echo green(), ' OK', PHP_EOL, endColor();
}

$cptRoutes = count($routes);

echo $cptRoutes , ' route(s) to process. Processing the route(s) ... ' . PHP_EOL;
for($i = 0; $i < $cptRoutes; $i += 1){
  $route = current($routes);
  $name = key($routes);
  next($routes);
  echo lightBlue(), str_pad($name, 25, ' '), lightGray();
  if(!isset($route['resources'])){
    echo status('Nothing to do', 'cyan'), ' =>', green(), ' OK', endColor(), PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  $chunks = $route['chunks'];
  $shaName = sha1('ca' . $name . VERSION . 'che');
  $bundlePath = BASE_PATH . '/bundles/' . $chunks[1] . '/';

  if(($mask & 2) >> 1)
    echo css($shaName, $chunks, $bundlePath, $resources);
  if(($mask & 4) >> 2)
    echo js($shaName, $chunks, $bundlePath, $resources);
  if($mask & 1)
    echo template($shaName, $name, $resources);

  echo ' => ', green(), 'OK ', endColor(), '[', cyan(), $shaName, endColor(), ']', PHP_EOL;
}

function status($status, $color = 'green'){ return ' [' . $color() . $status . lightGray(). ']'; }

/**
 * Cleans the css (spaces and comments)
 *
 * @param $content The css content to clean
 *
 * @return string The cleaned css
 */
function cleanCss($content)
{
  $content = preg_replace('@/\*.*?\*/@s', '', $content);
  $content = str_replace(array("\r\n", "\r", "\n", "\t", '  '), '', $content);
  $content = str_replace(array('{ ',' {'), '{', $content);
  $content = str_replace(array(' }','} '), '}', $content);
  $content = str_replace(array('; ',' ;'), ';', $content);
  $content = str_replace(array(', ',' ,'), ',', $content);

  return str_replace(': ', ':', $content);
}

/** Generates the gzipped css files
*
* @param string $shaName    Name of the cached file
* @param array  $chunks     Route site path
* @param string $bundlePath
* @param array  $resources  Resources array from the defined routes of the site
*/
function css($shaName, array $chunks, $bundlePath, array $resources){
  ob_start();
  loadResource($resources, $chunks, 'first_css', $bundlePath);
  loadResource($resources, $chunks, 'bundle_css', $bundlePath, '');
  loadResource($resources, $chunks, 'module_css', $bundlePath, $chunks[2] . '/');
  loadResource($resources, $chunks, '_css', $bundlePath);
  $allCss = ob_get_clean();

  if('' == $allCss)
    return status('No CSS', 'cyan');

  $allCss = cleanCss($allCss);
  $pathAndFile = CACHE_PATH . 'css/' . $shaName . '.css';
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, $allCss);
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  return status('CSS');
}

/**
 *  Generates the gzipped js files
 *
 * @param string $shaName   Name of the cached file
 * @param array  $chunks    Route site path
 * @param string $bundlePath
 * @param array  $resources Resources array from the defined routes of the site
 */
function js($shaName, array $chunks, $bundlePath, array $resources){
  ob_start();
  loadResource($resources, $chunks, 'first_js', $bundlePath);
  loadResource($resources, $chunks, 'bundle_js', $bundlePath, '');
  loadResource($resources, $chunks, 'module_js', $bundlePath . $chunks[2] . '/');
  loadResource($resources, $chunks, '_js', $bundlePath);
  $allJs = ob_get_clean();

  if('' == $allJs)
    return status('No JS', 'cyan');

  $pathAndFile = CACHE_PATH . 'js/' . $shaName . '.js';
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, $allJs);
  fclose($fp);
  exec('gzip -f -7 ' . $pathAndFile);
  // exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
  // exec('jamvm -Xmx32m -jar ../lib/compiler.jar --js ' . $pathAndFile . ' --js_output_file ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
  return status('JS');
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
function loadResource(array $resources, array $chunks, $key, $bundlePath, $path = true){
  if(isset($resources[$key]))
  {
    $type = substr(strrchr($key, '_'), 1);
    $path = $bundlePath . (($path)
      ?  $chunks[2] . '/resources/' . $type . '/'
      : $path . 'resources/' . $type . '/');

    foreach($resources[$key] as $resource)
    {
      if(false === strpos($resource, 'http'))
        echo file_get_contents($path . $resource . '.' . $type);
      else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resource . '.' . $type);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
    }
  }
}

/**
 * Generates the gzipped css files
 *
 * @param string $shaName   Name of the cached file
 * @param string $route
 * @param array  $resources Resources array from the defined routes of the site
 */
function template($shaName, $route, array $resources){
  if(!isset($resources['template']))
    return status('No TEMPLATE', 'cyan');

  ob_start();
  call_user_func('bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init');

  \lib\myLibs\core\Router::get($route);
  $content = ob_get_clean();

  $pathAndFile = CACHE_PATH . 'tpl/' . $shaName . '.html';
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, preg_replace('/>\s+</', '><', $content));
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  return status('TEMPLATE');
}
