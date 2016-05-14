<?
/**
 * THE framework global config
 *
 * @author Lionel Péramo */

namespace config;
use lib\myLibs\Session;

define('CACHE_PATH', BASE_PATH . 'cache/');

// CMS core resources
define('CMS_VIEWS_PATH', BASE_PATH . 'bundles/CMS/views/');
define('CMS_CSS_PATH', BASE_PATH . 'bundles/CMS/resources/css/');
define('CMS_JS_PATH', BASE_PATH . 'bundles/CMS/resources/js/');

// Framework core resources
define('CORE_RESOURCES_PATH', '/lib/myLibs/'); // Finit avec /
define('CORE_VIEWS_PATH', CORE_PATH . 'views/');
define('CORE_CSS_PATH', CORE_RESOURCES_PATH . 'resources/css/');
define('CORE_JS_PATH', CORE_RESOURCES_PATH . 'resources/js/');

define('LAYOUT', CORE_VIEWS_PATH . 'layout.phtml'); // It has to be layout

define('VERSION', 'v1');
define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters
define('FWK_HASH', '$2y$07$hu3yJ9cEtjFXwzpHoMdv5n');

// require_once 'cause maybe the class Lionel_Exception will attempt to load it too !
require_once BASE_PATH . 'config/' . XMODE . '/All_Config.php';

// External configuration loading
$dir = BASE_PATH . 'config/external/';
$dh = opendir($dir);

while (false !== ($file = readdir($dh)))
{
  $file = $dir . $file;
  if(is_dir($file)) continue;

  require $file;
}
closedir($dh);
?>
