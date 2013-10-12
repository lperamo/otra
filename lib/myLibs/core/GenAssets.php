<?
define('BASE_PATH', substr(__DIR__, 0, -15)); // Finit avec /
// BASE_PATH = __DIR__ . '/../../..';
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

    if(($mask & 2) >> 1)
    $file = CACHE_PATH . 'css/' . $shaName . '.css';
    if(file_exists($file))
      unlink($file);

    if(($mask & 4) >> 2)
    {
      $file = CACHE_PATH . 'js/' . $shaName . '.js';
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

  array_map('unlink', glob(\Config\All_Config::$cache_path . '/css/*'));
  array_map('unlink', glob(\Config\All_Config::$cache_path . '/js/*'));

  echo green(), ' OK', PHP_EOL, endColor();
}

function status($status, $color = 'green'){ return ' [' . $color() . $status . lightGray(). ']'; }

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
  $shaName = sha1('ca' . $name . VERSION . 'che');

  if(($mask & 2) >> 1)
    css($shaName, $resources);
  if(($mask & 4) >> 2)
    js($shaName, $resources);
  if($mask & 1)
    template($shaName, $name, $route['chunks'], $resources);

  echo ' => ', green(), 'OK ', endColor(), '[', cyan(), $shaName, endColor(), ']', PHP_EOL;
}

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

function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache')
{
  return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
}

/** Generates the gzipped css files
*
* @param string $shaName   Name of the cached file
* @param array  $resources Resources array from the defined routes of the site
*/
function css($shaName, array $resources){
  ob_start();

  if(isset($resources['cmsCss'])) {
    foreach($resources['cmsCss'] as $cmsCss) {
      require BASE_PATH . CMS_CSS_PATH . $cmsCss . '.css';
    }
  }

  if(isset($resources['css'])) {
    foreach($resources['css'] as $css) {
      require BASE_PATH . $css . '.css';
    }
  }
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
* @param array  $resources Resources array from the defined routes of the site
*/
function js($shaName, array $resources){
  ob_start();

  loadJS($resources, 'firstJs');

  if(isset($resources['cmsJs'])) {
    foreach($resources['cmsJs'] as $cmsJs) {
      require BASE_PATH . CMS_JS_PATH . $cmsJs . '.js';
    }
  }

  loadJS($resources, 'js');

  $allJs = ob_get_clean();

  if('' == $allJs){
    echo status('No JS', 'cyan');
  }else{
    $pathAndFile = CACHE_PATH . 'js/' . $shaName . '.js';
    $fp = fopen($pathAndFile, 'w');
    fwrite($fp, $allJs);
    fclose($fp);
    exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
  }
  echo status('JS');
}

function loadJs(array $resources, $key){
  if(isset($resources[$key])) {
    foreach($resources[$key] as $js) {
      if(false === strpos($js, 'http'))
        require BASE_PATH . $js . '.js';
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
