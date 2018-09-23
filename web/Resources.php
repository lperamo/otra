<? // 12ms of Apache before here
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
$uri = $_SERVER['REDIRECT_URL'] ?? strtok($_SERVER["REQUEST_URI"],'?');
define ('_DIR_', str_replace('\\', '/', __DIR__));

$realPath = substr(_DIR_, 0, -4) . $uri;

if ('/' !== $uri && true === file_exists($realPath))
{
  $posDot = strpos($uri, '.');
  // Comment it for debugging purposes
  if (true === isset($_SERVER['HTTP_REFERER']))
  {
    // Verifies that we went from the site and whether the file have an extension or not
    $ext = substr($uri, $posDot + 1);

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
        default: // images or other things
          // IE doesn't understand images correctly if there are a 'nosniff' header rule (for security)... -_-
          $userAgent = $_SERVER['HTTP_USER_AGENT'];
          preg_match('/MSIE (.*?);/', $userAgent, $matches);

          if (count($matches) < 2)
            preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $userAgent, $matches);

          if (count($matches) > 1 || false !== strpos($userAgent, 'Edge'))
          {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            header('Content-type: ' . finfo_file($finfo, $realPath));
            finfo_close($finfo);
          }
      }
    }

    /** TODO certain resources can be seen in a raw way via this line */
    echo file_get_contents($realPath);
    return 0;
  }

  // User can't see a resource directly so => 404
  header('HTTP/1.0 404 Not Found');
  return 0;
}

define('BASE_PATH', substr(_DIR_, 0, -3)); // Finit avec /
define('CORE_PATH', BASE_PATH . 'lib/myLibs/'); // Finit avec /
$uri = $_SERVER['REQUEST_URI'];
define('DEBUG_KEY', 'debuglp_');
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true
]);

// Will be the future translation feature
function t(string $texte) : string { return $texte; }

if (true === isset($_SESSION[DEBUG_KEY]) && 'Dev' == $_SESSION[DEBUG_KEY]
  || true === isset($_GET[DEBUG_KEY]) && 'Dev' == $_GET[DEBUG_KEY])
  require CORE_PATH . 'BootstrapDev.php';
else // mode Prod
{
  try
  {
    // We ensure that the debug bar is no more active
    if (true === isset($_SESSION[DEBUG_KEY])) unset($_SESSION[DEBUG_KEY]);

    require BASE_PATH . 'cache/php/RouteManagement.php';

    if ($route = \cache\php\Router::getByPattern($uri))
    {
      header('Content-Type: text/html; charset=utf-8');
      header('Vary: Accept-Encoding,Accept-Language');

      // Is it a static page
      if ('cli' !== PHP_SAPI && true === isset(\cache\php\Routes::$_[$route[0]]['resources']['template']))
      {
        header('Content-Encoding: gzip');
        echo file_get_contents(BASE_PATH . 'cache/tpl/' . sha1('ca' . $route[0] . 'v1che') . '.gz'); // version to change
        exit;
      }

      // Otherwise for dynamic pages...
      define('XMODE', 'prod');

      error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
      ini_set('display_errors', 1);
      ini_set('html_errors', 1);

      /** CLASS MAPPING */
      require BASE_PATH . 'cache/php/ClassMap.php';

      spl_autoload_register(function ($className) {
        if (false === isset(CLASSMAP[$className]))
        {
          require_once CORE_PATH . 'Logger.php';
          \lib\myLibs\Logger::logTo(
            'Path not found for the class name : ' . $className . PHP_EOL .
              'Stack trace : ' . PHP_EOL .
              print_r(debug_backtrace(), true),
            'classNotFound'
          );
        } else
          require CLASSMAP[$className];
      });

      //// Init' the database and loads the found route
      // Loads the found route
      require BASE_PATH . 'cache/php/' . $route[0] . '.php';
      //    call_user_func('\cache\php\Init::Init');

      \cache\php\Router::get($route[0], $route[1]);
    }
  }catch(Exception $e)
  {
    // Logs the error for developers...
    require_once CORE_PATH . 'Logger.php';
    \lib\myLibs\Logger::logTo(
      'Exception : ' . $e->getMessage() . PHP_EOL .
      'Stack trace : '. PHP_EOL .
      print_r(debug_backtrace(), true),
      'unknownExceptions'
    );

    // and shows a message for users !
    echo 'Server in trouble. Please come back later !';
  }catch(Error $e)
  {
    // Logs the error for developers...
    require_once CORE_PATH . 'Logger.php';
    \lib\myLibs\Logger::logTo(
      'Fatal error : ' . $e->getMessage() . PHP_EOL .
        'Stack trace : '. PHP_EOL .
        print_r(debug_backtrace(), true),
      'unknownFatalErrors'
    );

    // and shows a message for users !
    echo 'Server in great trouble. Please come back later !';
  }
}
