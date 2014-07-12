<?
// We generate the class mapping file if we need it.
if(!(isset($argv[2]) && '0' == $argv[2])) {
  require('GenClassMap.php');
  echo PHP_EOL;
}

require BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/core/Router.php';
require BASE_PATH . '/cache/php/ClassMap.php';

// Declaration of the special t function for templates...
function t($texte){ echo $texte; }

// Checks that the folder of micro bootstraps exists
if(!file_exists($bootstrapPath = BASE_PATH . 'cache/php'))
  mkdir($bootstrapPath);

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !
$_SERVER['REMOTE_ADDR'] = 'console'; // in order to pass some conditions;

// We save the previous state of dev/prod mode
$mode = (isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_']);
$_SESSION['debuglp_'] = 'Dev';

call_user_func('bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init');

$_SESSION['filesToConcat'] = array();

// Change the classic autoloader in order to stock file names to concatenate
$splAutoloadFunctions = spl_autoload_functions ();
spl_autoload_unregister($splAutoloadFunctions[0]);
spl_autoload_register(function($className) use($classMap)
{
  require $classMap[$className];
  $_SESSION['filesToConcat'][]= $classMap[$className];
});

// Force to show all errors
ini_set('error_reporting', 1);
error_reporting(-1);

// Checks whether we want only one/many CORRECT route(s)
if(isset($argv[3]))
{
  $route = $argv[3];
  if(isset(\config\Routes::$_[$route]))
    $routes = array($route => \config\Routes::$_[$route]);
  else
  {
    echo 'This route doesn\'t exist !', PHP_EOL;
    return;
  }
  echo 'Generating \'micro\' bootstrap for the route \'', $route, '\'', PHP_EOL, PHP_EOL;
} else {
  $routes = \config\Routes::$_;
  echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
}

foreach($routes as $route => $params)
{
  echo $route;
  flush();

  // Preparation of default parameters for the routes
  $chunks = $params['chunks'];

  if(isset($params['post']))
    $_POST = $params['post'];

  if(isset($params['get']))
    $_GET = $params['get'];

  if(isset($params['session'])) {
    foreach($params['session'] as $key => $param){
      $_SESSION[$key] = $param;
    }
  }

  // We execute the route...
  ob_start();

  try
  {
    \lib\myLibs\core\Router::get(
      $route,
      (isset($params['bootstrap'])) ? $params['bootstrap'] : ''
    );
    ob_end_clean();
    echo green(), str_pad('Can execute it.', 35 - strlen($route), ' ', STR_PAD_LEFT), endColor(), PHP_EOL;
  } catch(Exception $e)
  {
    ob_end_clean();
    echo red(), str_pad('Fail.', 35 - strlen($route), ' ', STR_PAD_LEFT), PHP_EOL,
      str_pad('- ', 5, ' ', STR_PAD_LEFT) . $e->getMessage(), endColor(), PHP_EOL;

    continue;
  }

  // ...and retrieve the PHP classes used and concatenate them
  $content = '';
  $filesToConcat = $_SESSION['filesToConcat'];
  $_SESSION['filesToConcat'] = array();

  foreach($filesToConcat as $file) { $content .= file_get_contents($file); }

  // We fix the created problems, check syntax errors and then minifies it
  $file = $bootstrapPath . '/' . $route;
  $file_ = $file . '_.php';

  contentToFile(fixUses($content), $file_);
  unset($content);

  if(hasSyntaxErrors($file_))
    return;

  compressPHPFile($file_, $file);
}

// Final specific management for routes files
echo 'Create the specific routes management file... ';

$routesManagementFile = $bootstrapPath . '/RouteManagement_.php';

contentToFile(
  fixUses(
    //file_get_contents(BASE_PATH . '/cache/php/ClassMap.php') .
  file_get_contents(BASE_PATH . '/lib/myLibs/core/Router.php') .
  file_get_contents(BASE_PATH . '/config/Routes.php')),
   $routesManagementFile);

if(hasSyntaxErrors($routesManagementFile))
  return;

compressPHPFile($routesManagementFile, $bootstrapPath . '/RouteManagement');

// We restore the dev/prod state
if($mode)
  $_SESSION['debuglp_'] = 'Dev';
else
  unset($_SESSION['debuglp_']);

/****** FUNCTIONS *****/

function hasSyntaxErrors($file_){
  exec('php -l ' . $file_, $output); // Syntax verification

  if(false !== strpos($output[0], 'pars', 7)) {
    echo red(), ' Beware of the syntax errors in the file', $file_,'!', endColor(), PHP_EOL;
    // unlink($file_);
    return true;
  }
  echo green(), ' and no syntax errors', endColor(), PHP_EOL;
}

function compressPHPFile($fileToCompress, $outputFile){
  $fp = fopen($outputFile . '.php', 'w');
  //  // php_strip_whitespace doesn't not suppress double spaces in string and others. Beware of that rule, the preg_replace is dangerous !
  fwrite($fp, rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($fileToCompress)) . "\n"));
  // fwrite($fp, php_strip_whitespace($fileToCompress));
  fclose($fp);
  // rename($fileToCompress, $outputFile . '.php');

  unlink($fileToCompress);
}

function contentToFile($content, $outputFile) {
  $fp = fopen($outputFile, 'w');
  fwrite($fp, $content);
  fclose($fp);
}

/**
 * Corrects the usage of namespaces and uses into the concatenated content
 */
function fixUses($content)
{
  $splits = explode(',', $content);
  $contents = array();

  // We retrieves the "uses" (namespaces) and we use them to replace classes calls
  preg_match_all('@^\s{0,}use\s[^;]{1,};\s{0,}$@m', $content, $matches);

  foreach ($matches[0] as $match)
  {
    $matches2 = explode(',', $match);
    $matches2[0] = substr(trim($matches2[0]), 4);
    $lastKey = count($matches2) - 1;

    foreach($matches2 as $key => $match2)
    {
      $match2 = trim($match2);

      if($key == $lastKey)
        $match2 = substr($match2, 0, -1);

      $matchChunks = explode('\\', $match2);
      $classToReplace = array_pop($matchChunks);

      $content = str_replace($match2 . (($key == $lastKey) ? ';' : ','), '', $content);

      if('Router' == $classToReplace || 'Routes' == $classToReplace)
        $content = preg_replace('@(?<!class )' . addslashes($match2) . '@', $classToReplace, $content);
    }
  }

  // We refactor the namespaces
  return str_replace('use ', '', '<? namespace cache\php;' .
    substr(preg_replace(
        '@\s*namespace [^;]{1,};\s*@',
        '',
        preg_replace('@\?>\s*<\?@', '', $content)
    ), 2));
}
?>
