<? // 12ms of Apache before here
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
$uri = $_SERVER['REDIRECT_URL'];
$__DIR__ = str_replace('\\', '/', __DIR__);

if( false !== ($posDot = strpos($uri, '.')))
{
  // Verifies that we went from the site and whether the file have an extension or not
  $ext = substr($uri, $posDot + 1);
  // Comment it for debugging purposes
  if (true === isset($_SERVER['HTTP_REFERER']))
  {
    if('gz' === $ext)
    {
      if (false !== strpos($uri, 'css'))
        header('Content-type:  text/css');
      else if (false !== strpos($uri, 'js'))
        header('Content-type: application/javascript');
      else
        header('Content-type: text/html; charset=utf-8');

      header('Content-Encoding: gzip');
    } else
    {
      switch($ext)
      {
        case 'css': header('Content-type:  text/css'); break;
        case 'js': header('Content-type: application/javascript'); break;
        case 'woff': header('Content-type: application/x-font-woff');
      }
    }

    die(file_get_contents(substr($__DIR__, 0, -4) . $uri));
  }

  // User can't see a resource directly so => 404
  header('HTTP/1.0 404 Not Found');
  return 0;
}

define('BASE_PATH', substr($__DIR__, 0, -3)); // Finit avec /
define('CORE_PATH', BASE_PATH . 'lib/myLibs/'); // Finit avec /
$uri = $_SERVER['REQUEST_URI'];
define('DEBUG_KEY', 'debuglp_');
session_start();

// Will be the future translation feature
function t(string $texte) : string { return $texte; }

if (true === isset($_SESSION[DEBUG_KEY]) && 'Dev' == $_SESSION[DEBUG_KEY]
  || true === isset($_GET[DEBUG_KEY]) && 'Dev' == $_GET[DEBUG_KEY])
  require CORE_PATH . 'Bootstrap_Dev.php';
else // mode Prod
{
  // We ensure that the debug bar is no more active
  if (true === isset($_SESSION[DEBUG_KEY])) unset($_SESSION[DEBUG_KEY]);

  require BASE_PATH . 'cache/php/RouteManagement.php';

  if ($route = \cache\php\Router::getByPattern($uri))
  {
    header('Content-Type: text/html; charset=utf-8');
    header('Vary: Accept-Encoding,Accept-Language');

    // Static page ?
    if('cli' !== PHP_SAPI && true === isset(\cache\php\Routes::$_[$route[0]]['resources']['template']))
    {
      header('Content-Encoding: gzip');
      echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . 'v1che') . '.gz'); // version to change
      exit;
    }

    define('XMODE', 'prod');

    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);

    /** CLASS MAPPING */
    require BASE_PATH . 'cache/php/ClassMap.php';

    spl_autoload_register(function($className)
    {
      if (false === isset(CLASSMAP[$className]))
        echo 'Path not found for the class name : ', $className, '<br>';
      else
        require CLASSMAP[$className];
    });

    //// Init' the database and loads the found route
    // Loads the found route
    require BASE_PATH . 'cache/php/' . $route[0] . '.php';
//    call_user_func('\cache\php\Init::Init');
    \cache\php\Router::get($route[0], $route[1]);
  }
}
