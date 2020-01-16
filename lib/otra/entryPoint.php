<?php

$uri = $_SERVER['REDIRECT_URL'] ?? strtok($_SERVER["REQUEST_URI"],'?');
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
        header('Content-type: text/css');
      else if (false !== strpos($uri, 'js'))
        header('Content-type: application/javascript');
      else
        header('Content-type: text/html; charset=utf-8');

      header('Content-Encoding: gzip');
    } else
    {
      switch($ext)
      {
        case 'css': header('Content-type: text/css'); break;
        case 'css.map':
        case 'js.map': header('Content-type: application/json'); break;
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

$uri = $_SERVER['REQUEST_URI'];
session_name('__Secure-LPSESSID');
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true
]);
?>
