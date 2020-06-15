<?php
declare(strict_types=1);
define('OTRA_KEY_REQUEST_URI', 'REQUEST_URI');
$_SERVER[APP_ENV] = $_ENV['OTRA_LIVE_APP_ENV'];
$_SERVER['HTTPS'] = $_ENV['OTRA_LIVE_HTTPS'] === 'true';

if (preg_match('@\.(?:png|ico|svg|json|webp|jpg|jpeg|gif|css|js).*$@', $_SERVER[OTRA_KEY_REQUEST_URI], $extension) !== false)
{
  switch($extension[0])
  {
    case '.css':
      header('Content-Type: text/css');
      break;
    case '.js':
      header('Content-Type: application/javascript');
      break;
    case '.json':
      header('Content-Type: application/json');
      break;
    default :
      $path = (mb_strpos($_SERVER[OTRA_KEY_REQUEST_URI], 'src')
        ? BASE_PATH
        : 'web'
        ) . $_SERVER[OTRA_KEY_REQUEST_URI];
      $finfo = finfo_open(FILEINFO_MIME_TYPE|FILEINFO_EXTENSION); // Retourne le type mime à l'extension mimetype
      $mime_type = finfo_file($finfo, $path);
      finfo_close($finfo);
      header('Content-Type:' . $mime_type);
  }

  if (in_array($extension[0], ['.css', '.js', '.json', '.gz']))
    $path = BASE_PATH . $_SERVER[OTRA_KEY_REQUEST_URI];

  echo file_get_contents($path);
  return true;
}

return false;

