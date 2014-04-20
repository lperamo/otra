<?
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
$uri = $_SERVER['REDIRECT_URL'];
define('BASE_PATH', substr(__DIR__, 0, -3)); // Finit avec /
define('BASE_PATH2', substr(__DIR__, 0, -4)); // Finit sans /

// $trueUri =
if(file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, BASE_PATH2 . $uri)))
{
  $smallUri = substr($uri, -7); // Better short !
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
        case 'js': header('Content-type: application/javascript'); break;
        case 'js.gz':
          header('Content-type: application/javascript');
          header('Content-Encoding: gzip');
          break;
        case 'woff': header('Content-type: application/x-font-woff'); break;
      }
      echo file_get_contents($file);
      die;
    }
    // The file user want to see a resource directly ()
    header('HTTP/1.0 404 Not Found');
    die;
  }
}

$uri = $_SERVER['REQUEST_URI'];
session_start();

// if($_SERVER['REQUEST_URI'] != '/backend/stats' && $_SERVER['REQUEST_URI'] != '/backend/ajax/users' && $_SERVER['REQUEST_URI'] != '/backend/ajax/stats' && 'Dev' == $_SESSION['debuglp_']){
//   var_dump($file, file_exists($file), $posDot);
//   echo '<pre>' , print_r($_SERVER, true), '<br />', print_r($uri, true),  '</pre>';
//     die;
// }
require BASE_PATH . 'lib/myLibs/core/Bootstrap_' . (('Dev' == $_SESSION['debuglp_'] || isset($_GET['debuglp_']) && 'Dev' == $_GET['debuglp_']) ?  'Dev.php' : 'Prod.php');
?>
