<?
$uri = $_SERVER['REQUEST_URI'];
$smallUri = substr($uri, -7);
$posDot = strpos($smallUri, '.');

if(false !== $posDot)
{
  $posDot = substr($smallUri, $posDot + 1);
  unset($smallUri);

  $css = ('css.gz' == $posDot);
  $js = ('js.gz' == $posDot);
  $woff = ('woff' == $posDot);

  if($css || $js || $woff)
  {
    if (file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, substr(__DIR__, 0, -4) . $uri)))
    {
      // Verify that we went from the site
      // if (isset($_SERVER['HTTP_REFERER'])) {
        if($css){
          header('Content-type: text/css');
          header('Content-Encoding: gzip');
        }elseif($js){
          header('Content-type: application/javascript');
          header("Content-Encoding: gzip");
        }else
          header('Content-type: application/x-font-woff');
        require $file;
        die;
      // }
    }
    header("HTTP/1.0 404 Not Found");
  }

  if (file_exists($file = str_replace('/', DIRECTORY_SEPARATOR, substr(__DIR__, 0, -4) . $uri)))
  {
    if('css' == $posDot){
      header('Content-type: text/css');
      require $file;
      die;
    }elseif('js' == $posDot){
      header('Content-type: application/javascript');
      require $file;
      die;
    }
  }
  header("HTTP/1.0 404 Not Found");
}else
  require('index.php');
?>
