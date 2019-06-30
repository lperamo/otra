<?
/**
 * THE framework global config
 *
 * @author Lionel Péramo */

namespace config;

define('CACHE_PATH', BASE_PATH . 'cache/');

// Framework core resources
define('CORE_RESOURCES_PATH', '/lib/myLibs/'); // ends with /
define('CORE_VIEWS_PATH', CORE_PATH . 'views/');
define('CORE_CSS_PATH', CORE_RESOURCES_PATH . 'resources/css/');
define('CORE_JS_PATH', CORE_RESOURCES_PATH . 'resources/js/');

define('LAYOUT', BASE_PATH . 'bundles/views/layout.phtml'); // It has to be layout

define('VERSION', 'v1');
define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters
define('FWK_HASH', '$2y$07$hu3yJ9cEtjFXwzpHoMdv5n');

// require_once 'cause maybe the class LionelException will attempt to load it too !
require_once BASE_PATH . 'config/' . $_SERVER['APP_ENV'] . '/AllConfig.php';

$externalConfigFile = BASE_PATH . 'bundles/config/Config.php';

if (true === file_exists($externalConfigFile))
  require $externalConfigFile;
?>
