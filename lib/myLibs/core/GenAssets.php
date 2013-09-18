<?
$basePath = __DIR__ . '/../../..';
require $basePath . '/config/Routes.php';
require_once $basePath . '/config/All_Config.php';
require $basePath . '/lib/packerjs/JavaScriptPacker.php';

$routes = \config\Routes::$_;

echo PHP_EOL, 'Cleaning the resources cache...' . PHP_EOL;

// If we ask just for only one route
if(isset($argv[2]))
{
  $theRoute = $argv[2];
  if(isset($routes[$theRoute])){
    $routes = array($theRoute => $routes[$theRoute]);
    // Cleaning the files specific to the route passed in parameter
    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    $file = CACHE_PATH . 'css/' . $shaName . '.css';
    if(file_exists($file))
      unlink($file);

    $file = CACHE_PATH . 'js/' . $shaName . '.js';
    if(file_exists($file))
      unlink($file);
  } else
    die('This route doesn\'t exist');
}else
{
  array_map('unlink', glob(\Config\All_Config::$cache_path . '/css/*'));
  array_map('unlink', glob(\Config\All_Config::$cache_path . '/js/*'));
}

$cptRoutes = count($routes);

echo $cptRoutes , ' route(s) to process. Processing the route(s) ... ' . PHP_EOL;
for($i = 0; $i < $cptRoutes; $i += 1){
  $route = current($routes);
  $name = key($routes);
  next($routes);
  echo $name;
  if(!isset($route['resources'])){
    echo ' (no resources) => Done', PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  $shaName = sha1('ca' . $name . VERSION . 'che');
  css($basePath, $shaName, $resources);
  js($basePath, $shaName, $resources);

  echo ' => Done', PHP_EOL;
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

/**
 * Cleans the js (spaces and comments)
 * TODO optimize with return new JavaScriptPacker($content)->pack();
 *
 * @param $content The js content
 *
 * @return string The cleaned js
 */
function cleanJs($content)
{
  $packer = new \lib\packerjs\JavaScriptPacker($content);
  return $packer->pack();
}

function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache')
{
  return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
}

/**
* @param string $basePath
* @param string $shaName   Name of the cached file
* @param array  $resources Resources array from the defined routes of the site
*/
function css($basePath, $shaName, $resources){
  ob_start();

  if(isset($resources['cmsCss'])) {
    foreach($resources['cmsCss'] as $cmsCss) {
      require $basePath . CMS_CSS_PATH . $cmsCss . '.css';
    }
  }

  if(isset($resources['css'])) {
    foreach($resources['css'] as $css) {
      require $basePath . $css . '.css';
    }
  }
  $allCss = ob_get_clean();

  if('' == $allCss)
    echo ' (no CSS)';
  else
  {
    $allCss = cleanCss($allCss);
    $fp = fopen(CACHE_PATH . 'css/' . $shaName . '.css', 'w');
    fwrite($fp, $allCss);
    fclose($fp);
  }
}

/**
* @param string $basePath
* @param string $shaName   Name of the cached file
* @param array  $resources Resources array from the defined routes of the site
*/
function js($basePath, $shaName, $resources){
  ob_start();

  loadJS($resources, 'firstJs');

  if(isset($resources['cmsJs'])) {
    foreach($resources['cmsJs'] as $cmsJs) {
      require $basePath . CMS_JS_PATH . $cmsJs . '.js';
    }
  }

  loadJS($resources, 'js');

  $allJs = ob_get_clean();

  if('' == $allJs){
    echo ' (no JS)';
  }else{
    // $allJs = cleanJs($allJs);
    $fp = fopen(CACHE_PATH . 'js/' . $shaName . '.js', 'w');
    fwrite($fp, $allJs);
    fclose($fp);
  }
}

function loadJs($resources, $key){
  if(isset($resources[$key])) {
    foreach($resources[$key] as $js) {
      if(false === strpos($js, 'http'))
        require $basePath . $js . '.js';
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
?>
