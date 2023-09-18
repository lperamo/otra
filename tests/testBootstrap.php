<?php
declare(strict_types=1);

namespace otra\bin;

use const otra\cache\php\CLASSMAP;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, CONSOLE_PATH, TEST_PATH};
use function otra\tools\delTree;

define(__NAMESPACE__ . '\\OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
require __DIR__ . (OTRA_PROJECT
  ? '/../../../..' // long path from vendor
  : '/..'
  ) . '/config/constants.php';

const
  CACHE_PHP_INIT_PATH = CACHE_PATH . 'php/init/',
  TASK_CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

error_reporting(E_ALL);

if (file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
{
  require CACHE_PHP_INIT_PATH . 'ClassMap.php';

  spl_autoload_register(function (string $className): void
  {
    if (!isset(CLASSMAP[$className]))
    {
      // Handle the particular test configuration
      if ('AllConfig' === $className)
        require TEST_PATH . 'config/AllConfig.php';
      elseif ('PHPUnit\Composer\Autoload\ClassLoader' === $className || 'PHPUnit\Framework\TestCase' === $className)
        return;
      else
        echo PHP_EOL, 'Path not found for the class name : ', $className, PHP_EOL;
    } else
      require CLASSMAP[$className];
  });

  require CONSOLE_PATH . 'colors.php';
  require CORE_PATH . 'tools/removeFieldProtection.php';

  if (!OTRA_PROJECT && file_exists(BUNDLES_PATH))
  {
    require CORE_PATH . 'tools/deleteTree.php';
    delTree(BUNDLES_PATH);
  }
}
