<?php
declare(strict_types=1);

/**
 * THE framework global config
 *
 * @author Lionel Péramo */

namespace config;

define('VERSION', '1.0.0-alpha.2.4.0');
define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters

// require_once 'cause maybe the class OtraException will attempt to load it too !
require_once BASE_PATH . 'config/' . $_SERVER[APP_ENV] . '/AllConfig.php';

$externalConfigFile = BASE_PATH . 'bundles/config/Config.php';

if (true === file_exists($externalConfigFile))
  require $externalConfigFile;

