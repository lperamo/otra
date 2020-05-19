<?php
$vendorPosition = mb_strrpos(__DIR__, 'vendor');
$otraProject = $vendorPosition !== false;
$basePath = $otraProject
  ? substr(__DIR__, 0, $vendorPosition)
  : substr(__DIR__, 0, mb_strrpos(__DIR__, 'src'));
$otraProjectSuffix = $otraProject ? 'vendor/otra/otra/src/' : 'src/';
$corePath = $basePath . $otraProjectSuffix;
$consolePath = $corePath . 'console/';
$coreResourcesPath = '/' . $otraProjectSuffix . 'resources/';
$cachePath = $basePath . 'cache/';
$testPath = $otraProject === true
  ? $basePath . 'vendor/otra/otra/tests/'
  : $basePath . 'tests/';
$content = '<?php define(\'BASE_PATH\',\'' . $basePath .
  '\');define(\'CORE_PATH\',\'' . $corePath .
  '\');define(\'CACHE_PATH\',\'' . $cachePath .
  '\');define(\'CONSOLE_PATH\',\'' . $consolePath .
  '\');define(\'CLASS_MAP_PATH\',\'' . $cachePath . 'php/ClassMap.php' .
  '\');define(\'TEST_PATH\',\'' . ($otraProject === true
    ? $basePath . 'vendor/otra/otra/tests/'
    : $basePath . 'tests/') .
  '\');define(\'CORE_VIEWS_PATH\',\'' . $corePath . 'views/' .
  '\');define(\'CORE_RESOURCES_PATH\',\'' . $coreResourcesPath .
  '\');define(\'CORE_CSS_PATH\',\'' . $coreResourcesPath . 'css/' .
  '\');define(\'CORE_JS_PATH\',\'' . $coreResourcesPath . 'js/' .
  '\');define(\'SPACE_INDENT\',\'  ' .
  '\');define(\'OTRA_VERSION\',\'1.0.0-alpha.2.3.0' .
  '\');if(!defined(\'OTRA_PROJECT\'))define(\'OTRA_PROJECT\',' . ($otraProject ? 'true' : 'false') . ');?>';

// require_once in case we do not load this file directly (console already loads colors, not composer)
require_once $consolePath . 'colors.php';

// Those lines are for when a developer installs OTRA via composer for the first time
$configFolderPath = $basePath . 'config/';

if (!file_exists($configFolderPath))
  mkdir($configFolderPath);

if (file_put_contents($basePath . 'config/constants.php', $content) === false)
  echo CLI_RED . 'There was a problem while writing the OTRA global constants.', END_COLOR, PHP_EOL;
else
  echo 'OTRA global constants generated.', CLI_GREEN, ' ✔', END_COLOR, PHP_EOL;
?>
