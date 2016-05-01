<? // 12ms of Apache before here
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
$uri = $_SERVER['REDIRECT_URL'];
define('DS', '/'); // Fixes windows awful __DIR__
$__DIR__ = str_replace('\\', '/', __DIR__);
define('BASE_PATH2', substr($__DIR__, 0, -4)); // Finit sans /

if(false !== ($posDot = strpos($uri, '.')))
{
  $uri = $_SERVER['REDIRECT_URL'];

  // Verifies that we went from the site and whether the file have an extension or not
  $ext = substr($uri, $posDot + 1);
  // Comment it for debugging purposes
  if(isset($_SERVER['HTTP_REFERER']))
  {
    if('gz' === $ext)
    {
      if(false !== strpos($uri, 'css'))
        header('Content-type:  text/css');
      else if(false !== strpos($uri, 'js'))
        header('Content-type: application/javascript');
      else
        header('Content-type: text/html; charset=utf-8');

      header('Content-Encoding: gzip');
    } else {
      switch($ext) {
        case 'css': header('Content-type:  text/css'); break;
        case 'js': header('Content-type: application/javascript'); break;
        case 'woff': header('Content-type: application/x-font-woff');
      }
    }

    die(file_get_contents(str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri)));
  }

  // User can't see a resource directly so => 404
  header('HTTP/1.0 404 Not Found');
  return 0;
}

define('BASE_PATH', substr($__DIR__, 0, -3)); // Finit avec /
$uri = $_SERVER['REQUEST_URI'];
$debugKey = 'debuglp_';
session_start();

if ((isset($_SESSION[$debugKey]) && 'Dev' == $_SESSION[$debugKey]) || isset($_GET[$debugKey]) && 'Dev' == $_GET[$debugKey])
  require BASE_PATH . 'lib/myLibs/core/Bootstrap_Dev.php';
else // mode Prod
{
  // We ensure that the debug bar is no more active
  if(isset($_SESSION[$debugKey])) unset($_SESSION[$debugKey]);

  require BASE_PATH . 'cache/php/RouteManagement.php';

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

    // Will be the future translation feature
    function t(string $texte) { echo $texte; }
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);

    // We load the class mapping
    require BASE_PATH . 'cache/php/ClassMap.php';
    spl_autoload_register(function($className) use($classMap)
    {
      if (false === isset($classMap[$className]))
        echo 'Path not found for the class name : ', $className, '<BR>';
      else
        require $classMap[$className];
    });

    // Init' the database and loads the found route
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';
    call_user_func('\cache\php\Init::Init');
    \cache\php\Router::get($route[0], $route[1]);
  }
}
