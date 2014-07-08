<? // 12ms of Apache before here
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */

ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('error_reporting', 1);
error_reporting(-1);

$uri = $_SERVER['REDIRECT_URL'];
define('BASE_PATH', substr(__DIR__, 0, -3)); // Finit avec /
define('BASE_PATH2', substr(__DIR__, 0, -4)); // Finit sans /

if(file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri)))
{
  $smallUri = substr($uri, -7); // Better short !
  $posDot = strpos($smallUri, '.');

  // Verify that we went from the site and whether the file have an extension or not
  if(false !== $posDot)
  {
    $ext = substr($smallUri, $posDot + 1);
    if(true || isset($_SERVER['HTTP_REFERER']))
    {
        if('gz' == $ext)
        {
          if(false !== strpos($uri, 'css'))
            header('Content-type:  text/css');
          else if(false !== strpos($uri, 'js'))
            header('Content-type: application/javascript');
          else
            header('Content-type: text/html');

          header('Content-Encoding: gzip');
          die(file_get_contents($file));
        }

        switch($ext){
          case 'css': header('Content-type:  text/css'); break;
          case 'js': header('Content-type: application/javascript'); break;
          case 'woff': header('Content-type: application/x-font-woff'); break;
        }
        die(file_get_contents($file));
    }

    // The file user want to see a resource directly ()
    header('HTTP/1.0 404 Not Found');
    die;
  }
}

// if the file has an extension but doesn't exist ...just die !
$fileParts = explode('/', $file);
if(false != strstr($fileParts[count($fileParts) - 1], '.'))
  die;

$uri = $_SERVER['REQUEST_URI'];
session_start();

if((((isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_']) || isset($_GET['debuglp_']) && 'Dev' == $_GET['debuglp_'])))
  require BASE_PATH . 'lib/myLibs/core/Bootstrap_Dev.php';
else // old Bootstrap_Prod.php
{
  if(isset($_SESSION['debuglp_']))
    unset($_SESSION['debuglp_']);

  require BASE_PATH . '/cache/php/RouteManagement.php';

  if($route = \cache\php\Router::getByPattern($uri))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');

    // if static
    if('cli' != PHP_SAPI && isset(\cache\php\Routes::$_[$route[0]]['resources']['template'])){
      header('Content-Encoding: gzip');
      require BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . 'v1che') . '.gz'; // version to change
      die;
    }

    define('XMODE', 'prod');

    function t($texte){ echo $texte; }

    // if the pattern is in the routes and not static, launch the associated route
    call_user_func('\\bundles\\' . \cache\php\Routes::$default['bundle'] . '\\Init::Init');
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';
    \cache\php\Router::get($route[0], $route[1]);
  }
}
