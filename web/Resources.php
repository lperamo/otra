<?
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
$uri = $_SERVER['REQUEST_URI'];
define('BASE_PATH', substr(__DIR__, 0, -3)); // Finit avec /
define('BASE_PATH2', substr(__DIR__, 0, -4)); // Finit sans /

// echo '<pre>', print_r($_SERVER, true), '</pre>';die;
if(file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri)))
{
  // echo $uri;
  $smallUri = substr($uri, -7);
  $posDot = strpos($smallUri, '.');
  // Verify that we went from the site and whether the file have an extension or not
  if(false !== $posDot)
  {
    if(isset($_SERVER['HTTP_REFERER']))
    {
      switch(substr($smallUri, $posDot + 1)){
        case 'css': header('Content-type:  text/css'); break;
        case 'css.gz':
          header('Content-type:  text/css');
          header('Content-Encoding: gzip');
          break;
        case 'js': header('Content-type: application/javascript');
        case 'js.gz':
          header('Content-type: application/javascript');
          header("Content-Encoding: gzip");
          break;
        case 'woff': header('Content-type: application/x-font-woff'); break;
        default:
          header('HTTP/1.0 404 Not Found'); die;
      }
      echo file_get_contents($file);
      die;
    }
    // The file user want to see a resource directly ()
    header('HTTP/1.0 404 Not Found');
    die;
  }
}

session_start();
require ('Dev' == $_SESSION['debuglp_'] || (isset($_GET['debuglp_']) && 'Dev' == $_GET['debuglp_'])) ? BASE_PATH . 'lib/myLibs/core/Bootstrap_Dev.php' : BASE_PATH . 'lib/myLibs/core/Bootstrap_Prod.php';
?>
