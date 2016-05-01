<?
require BASE_PATH . '/config/Routes.php';
require_once BASE_PATH . '/config/All_Config.php';
// require BASE_PATH . '/lib/packerjs/JavaScriptPacker.php';

$routes = \config\Routes::$_;

// If we ask just for only one route
if(true === isset($argv[3]))
{
  $theRoute = $argv[3];

  if(true === isset($routes[$theRoute]))
  {
    echo 'Cleaning the resources cache...';
    $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

    $routes = array($theRoute => $routes[$theRoute]);
    // Cleaning the files specific to the route passed in parameter
    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    if($mask & 1)
    {
      $file = CACHE_PATH . 'tpl/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 2) >> 1)
    {
      $file = CACHE_PATH . 'css/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 4) >> 2)
    {
      $file = CACHE_PATH . 'js/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }
    echo lightGreen(), ' OK', PHP_EOL, endColor();
  } else
    dieC('yellow', PHP_EOL . 'This route doesn\'t exist !' . PHP_EOL);
}else
{
  echo PHP_EOL, 'Cleaning the resources cache...';
  $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

  if($mask & 1)
    array_map('unlink', glob(CACHE_PATH . 'tpl/*'));

  if(($mask & 2) >> 1)
    array_map('unlink', glob(CACHE_PATH . 'css/*'));

  if(($mask & 4) >> 2)
    array_map('unlink', glob(CACHE_PATH . 'js/*'));

  echo lightGreen(), ' OK', PHP_EOL, endColor();
}

$cptRoutes = count($routes);

echo $cptRoutes , ' route(s) to process. Processing the route(s) ... ' . PHP_EOL . PHP_EOL;

for($i = 0; $i < $cptRoutes; ++$i)
{
  $route = current($routes);
  $name = key($routes);
  next($routes);
  echo lightCyan(), str_pad($name, 25, ' '), lightGray();

  if(!isset($route['resources'])) {
    echo status('Nothing to do', 'cyan'), ' =>', lightGreenText(' OK'), PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  $chunks = $route['chunks'];
  $shaName = sha1('ca' . $name . VERSION . 'che');


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

  if(($mask & 2) >> 1)
    echo css($shaName, $chunks, $bundlePath, $resources);
  if(($mask & 4) >> 2)
    echo js($shaName, $chunks, $bundlePath, $resources);
  if($mask & 1)
    echo template($shaName, $name, $resources);

  echo ' => ', lightGreen(), 'OK ', endColor(), '[', cyan(), $shaName, endColor(), ']', PHP_EOL;
}

// helper function
function checkShellCommand($command) { return !empty(shell_exec("$command")); }

function status($status, $color = 'lightGreen'){ return ' [' . $color() . $status . lightGray(). ']'; }

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
 * @param string $bundlePath
 * @param array  $resources Resources array from the defined routes of the site
 *
 * @return string
 */
function css(string $shaName, array $chunks, string $bundlePath, array $resources) : string
{
  ob_start();
  loadResource($resources, $chunks, 'first_css', $bundlePath);
  loadResource($resources, $chunks, 'bundle_css', $bundlePath, '');
  loadResource($resources, $chunks, 'module_css', $bundlePath, $chunks[2] . '/');
  loadResource($resources, $chunks, '_css', $bundlePath);
  $allCss = ob_get_clean();

  if('' == $allCss)
    return status('No CSS', 'cyan');

  $allCss = cleanCss($allCss);
  $pathAndFile = CACHE_PATH . 'css/' . $shaName;
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
 *
 * @return string
 */
function js(string $shaName, array $chunks, string $bundlePath, array $resources)
{
  ob_start();
  loadResource($resources, $chunks, 'first_js', $bundlePath);
  loadResource($resources, $chunks, 'bundle_js', $bundlePath, '');
  loadResource($resources, $chunks, 'module_js', $bundlePath . $chunks[2] . '/');
  loadResource($resources, $chunks, '_js', $bundlePath);
  $allJs = ob_get_clean();

  if('' === $allJs)
    return status('No JS', 'cyan');

  $pathAndFile = CACHE_PATH . 'js/' . $shaName;
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, $allJs);
  fclose($fp);

  // Linux or Windows ? We have java or jamvm ?
  if(false === strpos(php_uname('s'), 'Windows'))
  {
    if(empty(exec('which java')))
    {
      exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . ' --type js; gzip -f -9 ' . $pathAndFile);
      return status('JS');
    }
  } else
  {
    if(empty(exec('where java')))
    {
      exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . ' --type js & gzip -f -9 ' . $pathAndFile);
      return status('JS');
    }
  }

  /** TODO Find a way to store the logs (and then remove -W QUIET), other thing interesting --compilation_level ADVANCED_OPTIMIZATIONS */
  exec('java -Xmx32m -Djava.util.logging.config.file=logging.properties -jar ../lib/compiler.jar --logging_level FINEST -W QUIET --js ' . $pathAndFile . ' --js_output_file ' . $pathAndFile . ' --language_in=ECMASCRIPT6_STRICT --language_out=ES5_STRICT & gzip -f -9 ' . $pathAndFile);
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
function loadResource(array $resources, array $chunks, $key, string $bundlePath, $path = true)
{
  if(true === isset($resources[$key]))
  {
    $type = substr(strrchr($key, '_'), 1);
    $path = $bundlePath . (($path)
      ? $chunks[2] . '/resources/' . $type . '/'
      : $path . 'resources/' . $type . '/');

    foreach($resources[$key] as &$resource)
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
 * Generates the gzipped template files
 *
 * @param string $shaName   Name of the cached file
 * @param string $route
 * @param array  $resources Resources array from the defined routes of the site
 *
 * @return string
 */
function template(string $shaName, string $route, array $resources) : string
{
  if(!isset($resources['template']))
    return status('No TEMPLATE', 'cyan');

  // We don't allow errors shown on production !
  $oldErrorReporting = error_reporting();
  error_reporting(0);

  ob_start();
  call_user_func('bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init');

  \lib\myLibs\core\Router::get($route);
  $content = ob_get_clean();

  error_reporting($oldErrorReporting);

  $pathAndFile = CACHE_PATH . 'tpl/' . $shaName;
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, preg_replace('@\s{2,}@', ' ', $content));
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  return status('TEMPLATE');
}
