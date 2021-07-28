<?php
/**
 * THE framework global config
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra\config;

use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH};

const
  VERSION = '1.0.0-alpha.2.5.0',
  RESOURCE_FILE_MIN_SIZE = 21000; // n characters

// require_once 'cause maybe the class OtraException will attempt to load it too !
require_once BASE_PATH . 'config/' . $_SERVER[APP_ENV] . '/AllConfig.php';

$externalConfigFile = BUNDLES_PATH . 'config/Config.php';

if (file_exists($externalConfigFile))
  require $externalConfigFile;
