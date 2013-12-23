<?php
/**
 * THE framework global config
 *
 * @author Lionel Péramo */

namespace config;
use lib\myLibs\core\Session;

define('CACHE_PATH', BASE_PATH . 'cache' . DS);

// CMS core resources
define('CMS_VIEWS_PATH', '../bundles/CMS/views/');
define('CMS_CSS_PATH', '/bundles/CMS/resources/css/');
define('CMS_JS_PATH', '/bundles/CMS/resources/js/');

// Framework core resources
define('CORE_VIEWS_PATH', '../lib/myLibs/core/views');
define('CORE_CSS_PATH', '/lib/myLibs/core/css/');
define('CORE_JS_PATH', '/lib/myLibs/core/js/');

define('LAYOUT', CORE_VIEWS_PATH . DS . 'layout.phtml');

define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters
define('VERSION', 'v1'); // to use with the finger printing technique (if modified via a script or manually, this will force the browsers to recache resources.)
define('FWK_HASH', '$2a$07$ThisoneIsanAwesomeframework$');

require XMODE . DS . 'All_Config.php';
