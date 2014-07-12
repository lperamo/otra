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

if(false !== strpos($uri, 'cache') || file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri)))
{
  // $file = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri);
  $file = BASE_PATH2. $uri;
  $uri = $_SERVER['REQUEST_URI'];
  $smallUri = substr($uri, -7); // Better short !

  // Verifies that we went from the site and whether the file have an extension or not
  $ext = substr($smallUri, strpos($smallUri, '.') + 1);
  if(true || isset($_SERVER['HTTP_REFERER']))
  {
      if('gz' == $ext)
      {
        if(false !== strpos($uri, 'css'))
          header('Content-type:  text/css');
        else if(false !== strpos($uri, 'js'))
          header('Content-type: application/javascript');
        else
          header('Content-type: text/html; charset=utf-8');

        header('Content-Encoding: gzip');
      } else {
        switch($ext){
          case 'css': header('Content-type:  text/css'); break;
          case 'js': header('Content-type: application/javascript'); break;
          case 'woff': header('Content-type: application/x-font-woff'); break;
        }
      }
      die(file_get_contents($file));
  }

  // User can't see a resource directly so => 404
  header('HTTP/1.0 404 Not Found');
  die;
}

$uri = $_SERVER['REQUEST_URI'];
session_start();

if ((isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_']) || isset($_GET['debuglp_']) && 'Dev' == $_GET['debuglp_'])
  require BASE_PATH . 'lib/myLibs/core/Bootstrap_Dev.php';
else // mode Prod
{
  if(isset($_SESSION['debuglp_'])) unset($_SESSION['debuglp_']);

  require BASE_PATH . '/cache/php/RouteManagement.php';

  if($route = \cache\php\Router::getByPattern($uri))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');
    // if static
    if('cli' != PHP_SAPI && isset(\cache\php\Routes::$_[$route[0]]['resources']['template']))
    {
      header('Content-Encoding: gzip');
      echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . 'v1che') . '.gz'); // version to change
      exit;
    }
    define('XMODE', 'prod');

    function t($texte){ echo $texte; }

    // Init' the database and loads the found route
    call_user_func('\\bundles\\' . \cache\php\Routes::$default['bundle'] . '\\Init::Init');
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';
    \cache\php\Router::get($route[0], $route[1]);
  }
}
