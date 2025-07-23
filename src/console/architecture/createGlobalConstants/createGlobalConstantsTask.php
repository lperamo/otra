<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createGlobalConstants;

use otra\config\AllConfig;
use const otra\cache\php\{APP_ENV, BASE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};
use function otra\tools\getconfigByEnvironment;

const
  BASE_PATH_STRING = 'BASE_PATH.\'',
  PHP_FILE_START = '<?php declare(strict_types=1);';

/**
 * @return void
 */
function createGlobalConstants() : void
{
  define(__NAMESPACE__ . '\\CURRENT_FOLDER', str_replace('\\', '/', __DIR__));
  $vendorPosition = mb_strrpos(CURRENT_FOLDER, 'vendor');
  $otraProject = $vendorPosition !== false;

  if (!defined('otra\\cache\\php\\BASE_PATH'))
    define('otra\\cache\\php\\BASE_PATH',
      $otraProject
        ? mb_substr(CURRENT_FOLDER, 0, $vendorPosition)
        : mb_substr(CURRENT_FOLDER, 0, mb_strrpos(CURRENT_FOLDER, 'src'))
    );

  if (!defined('otra\\cache\\php\\APP_ENV'))
  {
    define('otra\\cache\\php\\APP_ENV', 'APP_ENV');
    $_SERVER[APP_ENV] = 'prod';
    define('otra\\cache\\php\\CACHE_PATH', BASE_PATH . 'cache/');
    define('otra\\cache\\php\\DIR_SEPARATOR', '/');
    define('otra\\cache\\php\\PROD', 'prod');
  }

  define(__NAMESPACE__ . '\\OTRA_PROJECT_SUFFIX', $otraProject ? 'vendor/otra/otra/src/' : 'src/');
  define(__NAMESPACE__ . '\\CORE_RESOURCES_PATH', DIR_SEPARATOR . OTRA_PROJECT_SUFFIX . 'resources/');

  $content = 'const DEV=\'dev' .
    '\',PROD=\'prod' .
    '\',BASE_PATH=\'' . BASE_PATH .
    '\',BUNDLES_PATH=' . BASE_PATH_STRING . 'bundles/' .
    '\',CORE_PATH=' . BASE_PATH_STRING . OTRA_PROJECT_SUFFIX .
    '\',CACHE_PATH=' . BASE_PATH_STRING . 'cache/' .
    '\',CONSOLE_PATH=CORE_PATH.\'console/' .
    '\',CLASS_MAP_PATH=CACHE_PATH.\'php/init/ClassMap.php\',TEST_PATH=' .
    ($otraProject
      ? BASE_PATH_STRING . 'vendor/otra/otra/tests/'
      : BASE_PATH_STRING . 'tests/') .
    '\',CORE_VIEWS_PATH=CORE_PATH.\'views/' .
    '\',CORE_CSS_PATH=\'' . CORE_RESOURCES_PATH . 'css/' .
    '\',CORE_JS_PATH=\'' . CORE_RESOURCES_PATH . 'js/' .
    '\',SPACE_INDENT=\'  ' .
    '\',APP_ENV=\'APP_ENV' .
    '\',OTRA_VERSION=\'2025.0.0' .
    '\',DIR_SEPARATOR=\'/' .
    '\';if(!defined(__NAMESPACE__.\'\\\\OTRA_PROJECT\'))define(__NAMESPACE__.\'\\\\OTRA_PROJECT\',' .
    ($otraProject ? 'true' : 'false') . ');';

  // require_once in case we do not load this file directly (console already loads colors, not composer)
  require_once BASE_PATH . ($otraProject ? 'vendor/otra/otra/src' : 'src') . '/console/colors.php';

  // Those lines are for when a developer installs OTRA via composer for the first time
  $configFolderPath = BASE_PATH . 'config/';

  if (!file_exists($configFolderPath))
    mkdir($configFolderPath);

  echo (file_put_contents(
      BASE_PATH . 'config/constants.php',
      PHP_FILE_START . 'namespace otra\\cache\\php;' . $content
    ) === false)
    ? CLI_ERROR . 'There was a problem while writing the OTRA global constants.'
    : 'OTRA global constants for the ' . CLI_INFO_HIGHLIGHT . 'dev' . CLI_BASE . ' environment generated.' .
      CLI_SUCCESS . ' ✔';

  echo END_COLOR, PHP_EOL;

  // Needed when installing/updating OTRA
  if (!class_exists(AllConfig::class))
  {
    if (!defined('otra\\cache\\php\\BUNDLES_PATH'))
      define('otra\\cache\\php\\BUNDLES_PATH', BASE_PATH . 'bundles/');

    if (file_exists(BASE_PATH . 'config/AllConfig.php'))
      require BASE_PATH . 'config/AllConfig.php';
  }

  require BASE_PATH . OTRA_PROJECT_SUFFIX . 'tools/getConfigByEnvironment.php';

  $configPath = BASE_PATH . 'config/';
  $filesAndFoldersInConfigPath = scandir($configPath);
  $constantsBaseContent = PHP_FILE_START . 'namespace otra\\cache\\php;' . $content;

  foreach($filesAndFoldersInConfigPath as $fileOrFolder)
  {
    if (!is_dir($configPath . $fileOrFolder)
      || $fileOrFolder === '.'
      || $fileOrFolder === '..'
      || $fileOrFolder === 'dev')
      continue;

    $config = getConfigByEnvironment($fileOrFolder, ['remote'], false);
    $finalContent = $constantsBaseContent;

    if (isset($config['remote']['folder']))
      $finalContent =  str_replace(BASE_PATH, $config['remote']['folder'], $constantsBaseContent, $count);

    echo (file_put_contents(BASE_PATH . 'config/' . $fileOrFolder . 'Constants.php', $finalContent) === false)
      ? CLI_ERROR . 'There was a problem while writing the OTRA global constants for the ' . CLI_INFO_HIGHLIGHT .
      $fileOrFolder . CLI_ERROR . ' environment.' . PHP_EOL
      : 'OTRA global constants for the ' . CLI_INFO_HIGHLIGHT . $fileOrFolder . CLI_BASE . ' environment generated.' .
      CLI_SUCCESS . ' ✔' . CLI_BASE . PHP_EOL;
  }

  echo END_COLOR;
}
