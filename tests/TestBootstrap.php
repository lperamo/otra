<?php
// Fixes windows awful __DIR__
define('_DIR_', str_replace('\\', '/', __DIR__));
// if true, we are not developing on OTRA itself
define('OTRA_PROJECT', strpos(_DIR_, 'vendor') !== false);
// The path finishes with /
define(
  'BASE_PATH',
  OTRA_PROJECT === true
    ? substr(_DIR_, 0, -16) // 16 = strlen('vendor/otra/otra')
    : realpath(_DIR_ . '/..') . '/'
);

define(
  'CORE_PATH',
  OTRA_PROJECT === true
    ? BASE_PATH . 'vendor/otra/otra/src/'
    : BASE_PATH . 'src/'
);

define(
  'TEST_PATH',
  OTRA_PROJECT === true
    ? BASE_PATH . 'vendor/otra/otra/tests/'
    : BASE_PATH . 'tests/'
);

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
require CORE_PATH . 'tools/removeFieldProtection.php';
