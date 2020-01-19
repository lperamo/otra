<?php
define ('_DIR_', str_replace('\\', '/', __DIR__));
define('BASE_PATH', substr(_DIR_, 0, strpos(_DIR_, 'otra')) . 'otra/'); // Ends with /
define('CORE_PATH', BASE_PATH . 'src/otra/');
require BASE_PATH . 'cache/php/ClassMap.php';

spl_autoload_register(function(string $className)
{
  if (false === isset(CLASSMAP[$className]))
  {
    // Handle the particular test configuration
    if('AllConfig' === $className)
      require BASE_PATH . 'tests/config/AllConfig.php';
    else
      echo PHP_EOL, 'Path not found for the class name : ', $className, PHP_EOL;
  }else
    require CLASSMAP[$className];
});

require CORE_PATH . 'console/colors.php';
require CORE_PATH . 'tools/RemoveFieldProtection.php';