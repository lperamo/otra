<?php
declare(strict_types=1);
namespace otra\tests;
use function otra\tools\delTree;

define('OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
require __DIR__ . (OTRA_PROJECT
  ? '/../../../..' // long path from vendor
  : '/..'
  ) . '/config/constants.php';

const
  BUNDLES_PATH = BASE_PATH . 'bundles/',
  CACHE_PHP_INIT_PATH = CACHE_PATH . 'php/init/',
  TASK_CLASS_MAP_PATH = CACHE_PHP_INIT_PATH . 'tasksClassMap.php';

if (file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
{
  require CACHE_PHP_INIT_PATH . 'ClassMap.php';

  spl_autoload_register(function (string $className)
  {
    var_dump($className);
    if (!isset(CLASSMAP[$className]))
    {
      // Handle the particular test configuration
      if ('AllConfig' === $className)
        require TEST_PATH . 'config/AllConfig.php';
      else
        echo PHP_EOL, 'Path not found for the class name : ', $className, PHP_EOL;
    } else
      require CLASSMAP[$className];
  });

  require CONSOLE_PATH . 'colors.php';
  require CORE_PATH . 'tools/removeFieldProtection.php';

  if (!OTRA_PROJECT)
  {
    if (file_exists(BUNDLES_PATH))
      delTree(BUNDLES_PATH);
  }
}

