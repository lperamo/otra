<?
$basePath = __DIR__ . '/../../..';
require $basePath . '/config/Router.php';
require_once $basePath . '/config/All_Config.php';
require $basePath . '/lib/packerjs/JavaScriptPacker.php';

$routes = \config\Router::$routes;
$cptRoutes = count($routes);

echo PHP_EOL, $cptRoutes , ' routes to process. Processing the routes ... ' . PHP_EOL;
for($i = 0; $i < $cptRoutes; $i += 1){
  $route = current($routes);
  $nom = key($routes);
  next($routes);
  echo $nom;
  if(!isset($route['resources'])){
    echo ' (no resources) => Done', PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  css($basePath, $nom, $resources);
  js($basePath, $nom, $resources);

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

function css($basePath, $nom, $resources){
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

    $fp = fopen(getCacheFileName($nom . VERSION, CACHE_PATH . 'css/', '', '.css'), 'w');
    fwrite($fp, $allCss);
    fclose($fp);
  }
}

function js($basePath, $nom, $resources){
  ob_start();

  if(isset($resources['cmsJs'])) {
    foreach($resources['cmsJs'] as $cmsJs) {
      require $basePath . CMS_JS_PATH . $cmsJs . '.js';
    }
  }

  if(isset($resources['js'])) {
    foreach($resources['js'] as $js) {
      require $basePath . $js . '.js';
    }
  }

  $allJs = ob_get_clean();
  if('' == $allJs){
    echo ' (no JS)';
  }else{
    $allJs = cleanJs($allJs);

    $fp = fopen(getCacheFileName($nom . VERSION, CACHE_PATH . 'js/', '', '.js'), 'w');
    fwrite($fp, $allJs);
    fclose($fp);
  }
}
?>
