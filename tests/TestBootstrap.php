<?php
declare(strict_types=1);

define('OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
require __DIR__ . (OTRA_PROJECT
  ? '/../../../..' // long path from vendor
  : '/..'
  ) . '/config/constants.php';
define('BUNDLES_PATH', BASE_PATH . 'bundles/');

if (file_exists(CACHE_PATH . 'php/ClassMap.php'))
{
  require CACHE_PATH . 'php/ClassMap.php';

  spl_autoload_register(function (string $className)
  {
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
    /** @var Closure $delTree */
    require CORE_PATH . 'tools/deleteTree.php';

    if (file_exists(BUNDLES_PATH))
      $delTree(BUNDLES_PATH);
  }
}

