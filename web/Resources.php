<?php
$uri = $_SERVER['REQUEST_URI'];
$smallUri = substr($uri, -5);
$posDot = strpos($smallUri, '.');

if(false !== $posDot)
{
  $posDot = substr($smallUri, $posDot + 1);
  unset($smallUri);
  $css = ('css' == $posDot);
  $js = ('js' == $posDot);
  $woff = ('woff' == $posDot);

  if($css || $js || $woff)
  {
    $BASE_PATH = substr(__DIR__, 0, -4);
    $file = str_replace('/', DIRECTORY_SEPARATOR, $BASE_PATH . $uri);
    if (file_exists($file))
    {
      // Verify that we went from the site
      if (isset($_SERVER['HTTP_REFERER'])) {
        if($css)
          header('Content-type: text/css');
        elseif($js){
          header('Content-type: application/javascript');
        }else
          header('Content-type: application/x-font-woff');
        require($file);
        die;
      }
    }
    header("HTTP/1.0 404 Not Found");
  }
}else
  require('index.php');
?>
