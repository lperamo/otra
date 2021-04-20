<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

use config\AllConfig;

define('_DIR_', str_replace('\\', '/', __DIR__));
$vendorPosition = mb_strrpos(_DIR_, 'vendor');
$otraProject = $vendorPosition !== false;
$basePath = $otraProject
  ? substr(_DIR_, 0, $vendorPosition)
  : substr(_DIR_, 0, mb_strrpos(_DIR_, 'src'));
$otraProjectSuffix = $otraProject ? 'vendor/otra/otra/src/' : 'src/';
$corePath = $basePath . $otraProjectSuffix;
$consolePath = $corePath . 'console/';
$coreResourcesPath = '/' . $otraProjectSuffix . 'resources/';
$cachePath = $basePath . 'cache/';
$testPath = $otraProject === true
  ? $basePath . 'vendor/otra/otra/tests/'
  : $basePath . 'tests/';
$content = '<?php declare(strict_types=1);define(\'BASE_PATH\',\'' . $basePath .
  '\');define(\'CORE_PATH\',\'' . $corePath .
  '\');define(\'CACHE_PATH\',\'' . $cachePath .
  '\');define(\'CONSOLE_PATH\',\'' . $consolePath .
  '\');define(\'CLASS_MAP_PATH\',\'' . $cachePath . 'php/init/ClassMap.php' .
  '\');define(\'TEST_PATH\',\'' . ($otraProject === true
    ? $basePath . 'vendor/otra/otra/tests/'
    : $basePath . 'tests/') .
  '\');define(\'CORE_VIEWS_PATH\',\'' . $corePath . 'views/' .
  '\');define(\'CORE_RESOURCES_PATH\',\'' . $coreResourcesPath .
  '\');define(\'CORE_CSS_PATH\',\'' . $coreResourcesPath . 'css/' .
  '\');define(\'CORE_JS_PATH\',\'' . $coreResourcesPath . 'js/' .
  '\');define(\'SPACE_INDENT\',\'  ' .
  '\');define(\'APP_ENV\',\'APP_ENV' .
  '\');define(\'OTRA_VERSION\',\'1.0.0-alpha.2.4.0' .
  '\');if(!defined(\'OTRA_PROJECT\'))define(\'OTRA_PROJECT\',' . ($otraProject ? 'true' : 'false') . ');';

// require_once in case we do not load this file directly (console already loads colors, not composer)
require_once $consolePath . 'colors.php';

// Those lines are for when a developer installs OTRA via composer for the first time
$configFolderPath = $basePath . 'config/';

if (!file_exists($configFolderPath))
  mkdir($configFolderPath);

echo (file_put_contents($basePath . 'config/constants.php', $content) === false)
  ? CLI_ERROR . 'There was a problem while writing the OTRA global constants.'
  : 'OTRA global constants generated.', CLI_SUCCESS, ' ✔';

echo END_COLOR, PHP_EOL;

// On the online side
if (class_exists(AllConfig::class) && isset(AllConfig::$deployment['folder']))
{
  echo (file_put_contents(
    $basePath . 'config/prodConstants.php',
    str_replace($basePath, AllConfig::$deployment['folder'], $content, $count)
    ) === false)
    ? CLI_ERROR . 'There was a problem while writing the OTRA global constants for the online side.'
    : 'OTRA global constants for the online side generated.', CLI_SUCCESS, ' ✔';
}

echo END_COLOR, PHP_EOL;
