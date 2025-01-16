<?php
/**
 * THE framework global config
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace tests\examples\deployment\fixFiles\input\vendor;

use const otra\cache\php\{APP_ENV, BUNDLES_PATH, PROD, TEST_PATH};

const
  VERSION = '2025.0.0',
  RESOURCE_FILE_MIN_SIZE = 21000; // n characters

// require_once 'cause maybe the class OtraException will attempt to load it too !
require_once TEST_PATH . 'examples/deployment/fixFiles/input/vendor/' . $_SERVER[APP_ENV] . 'Config.php';

$externalConfigFile = BUNDLES_PATH . 'config/Config.php';

if (file_exists($externalConfigFile))
  require $externalConfigFile;

if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli')
  class_alias('otra\\cache\\php\\Router', 'otra\\Router');
