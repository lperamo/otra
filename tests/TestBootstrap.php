<?php

define('OTRA_PROJECT', strpos(__DIR__, 'vendor') !== false);
require __DIR__ . (OTRA_PROJECT
  ? '/../../../..' // long path from vendor
  : '/..'
  ) . '/config/constants.php';

require CACHE_PATH . 'php/ClassMap.php';

spl_autoload_register(function(string $className)
{
  if (false === isset(CLASSMAP[$className]))
  {
    // Handle the particular test configuration
    if('AllConfig' === $className)
      require TEST_PATH . 'config/AllConfig.php';
    else
      echo PHP_EOL, 'Path not found for the class name : ', $className, PHP_EOL;
  }else
    require CLASSMAP[$className];
});

require CONSOLE_PATH . 'colors.php';
require CORE_PATH . 'tools/removeFieldProtection.php';
