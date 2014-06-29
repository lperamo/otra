<?
// We generate the class mapping file if we need it.
if(!(isset($argv[2]) && '0' == $argv[2]))
  require('GenClassMap.php');

require BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/core/Router.php';
require ROOTPATH . 'lib/myLibs/core/ClassMap.php';

// Declaration of the special t function for templates...
function t($texte){ echo $texte; }

// Checks that the folder of micro bootstraps exists
if(!file_exists($bootstrapPath = BASE_PATH . 'cache/php'))
  mkdir($bootstrapPath);

$_SESSION['bootstrap'] = 1; // in order to not really make BDD requests !

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

echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;

foreach(\config\Routes::$_ as $route => $params)
{
  echo $route;
  ob_flush();flush();

  // Preparation of default parameters for the routes
  $chunks = $params['chunks'];

  if(isset($params['post']))
    $_POST = $params['post'];

  if(isset($params['session'])) {
    foreach($params['session'] as $key => $param){
      $_SESSION[$key] = $param;
    }
  }

  ob_start();

  try
  {
    \lib\myLibs\core\Router::get(
      $route,
      (isset($params['bootstrap'])) ? $params['bootstrap'] : ''
    );
    ob_end_clean();
    echo green(), str_pad('Done.', 30 - strlen($route), ' ', STR_PAD_LEFT), endColor(), PHP_EOL;
  } catch(Exception $e)
  {
    ob_end_clean();
    echo red(), str_pad('Fail.', 30 - strlen($route), ' ', STR_PAD_LEFT), PHP_EOL,
      str_pad('- ', 5, ' ', STR_PAD_LEFT) . $e->getMessage(), endColor(), PHP_EOL;
  }

  $content = '';
  $filesToConcat = $_SESSION['filesToConcat'];

  foreach($filesToConcat as $file) { $content .= file_get_contents($file); }

  $file = $bootstrapPath . '/' . $route . '.php';
  $fp = fopen($file, 'w');
  fwrite($fp, $content);
  fclose($fp);

  exec('php -l ' . $file); // Syntax verification

  // $fp = fopen(ROOTPATH . 'lib/myLibs/core/Bootstrap.php', 'w');
  // fwrite($fp, php_strip_whitespace($file));
  // fclose($fp);
}

// We restore the dev/prod state
if($mode)
  $_SESSION['debuglp_'] = 'Dev';
else
  unset($_SESSION['debuglp_']);
?>
