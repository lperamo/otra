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

  if(($mask & 2) >> 1)
    css($shaName, $chunks, $resources);
  if(($mask & 4) >> 2)
    js($shaName, $chunks, $resources);
  if($mask & 1)
    template($shaName, $name, $resources);

  echo ' => ', green(), 'OK ', endColor(), '[', cyan(), $shaName, endColor(), ']', PHP_EOL;
}

function status($status, $color = 'green'){ return ' [' . $color() . $status . lightGray(). ']'; }

/** Cleans the css (spaces and comments)
 *
 * @param $content The css content
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
* @param string $shaName   Name of the cached file
* @param array  $chunks    Route site path
* @param array  $resources Resources array from the defined routes of the site
*/
function css($shaName, array $chunks, array $resources){
  ob_start();

  if(isset($resources['cmsCss'])) {
    foreach($resources['cmsCss'] as $cmsCss) {
      echo file_get_contents(BASE_PATH . CMS_CSS_PATH . $cmsCss . '.css');
    }
  }

  loadResource($chunks, $resources, 'css');

  $allCss = ob_get_clean();

  if('' == $allCss)
    echo status('No CSS', 'cyan');
  else
  {
    $allCss = cleanCss($allCss);
    $pathAndFile = CACHE_PATH . 'css/' . $shaName . '.css';
    $fp = fopen($pathAndFile, 'w');
    fwrite($fp, $allCss);
    fclose($fp);
    exec('gzip -f -9 ' . $pathAndFile);
  }
  echo status('CSS');
}

/** Generates the gzipped js files
*
* @param string $shaName   Name of the cached file
* @param array  $chunks    Route site path
* @param array  $resources Resources array from the defined routes of the site
*/
function js($shaName, array $chunks, array $resources){
  ob_start();
  loadResource($resources, $chunks, 'firstJs');

  if(isset($resources['cmsJs'])) {
    foreach($resources['cmsJs'] as $cmsJs) {
      echo file_get_contents(BASE_PATH . CMS_JS_PATH . $cmsJs . '.js');
    }
  }

  loadResource($resources, $chunks, 'js');
  $allJs = ob_get_clean();

  if('' == $allJs){
    echo status('No JS', 'cyan');
  }else{
    $pathAndFile = CACHE_PATH . 'js/' . $shaName . '.js';
    $fp = fopen($pathAndFile, 'w');
    fwrite($fp, $allJs);
    fclose($fp);
    exec('gzip -f -9 ' . $pathAndFile);
    // exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
    // exec('jamvm -Xmx32m -jar ../lib/compiler.jar --js ' . $pathAndFile . ' --js_output_file ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
    echo status('JS');
  }
}

function loadResource(array $resources, $chunks, $key){
  if(isset($resources[$key])) {
    $path = BASE_PATH . '/bundles/' . $chunks[1] . '/modules/' . $chunks[2] . '/media/' . (('js' == substr($key, -2)) ? 'js' : $key) . '/';

    foreach($resources[$key] as $js) {
      if(false === strpos($js, 'http'))
        echo file_get_contents($path . $js . '.js');
      else{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $js . '.js');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
    }
  }
}

/** Generates the gzipped css files
 *
 * @param string $shaName   Name of the cached file
 * @param string $route
 * @param array  $resources Resources array from the defined routes of the site
 */
function template($shaName, $route, array $resources){
  if(!isset($resources['template'])){
    echo status('No TEMPLATE', 'cyan');
    return;
  }

  ob_start();
  call_user_func('bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init');
  \lib\myLibs\core\Router::get($route);
  $content = ob_get_clean();

  $pathAndFile = CACHE_PATH . 'tpl/' . $shaName . '.html';
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, preg_replace('/>\s+</', '><', $content));
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  echo status('TEMPLATE');
}
?>
