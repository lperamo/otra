<?php
define ('_DIR_', str_replace('\\', '/', __DIR__));
require _DIR_ . '/../cache/php/ClassMap.php';

spl_autoload_register(function($className)
{
  if (false === isset(CLASSMAP[$className])){
    echo PHP_EOL, 'Path not found for the class name : ', $className, PHP_EOL;
//    var_dump(debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
}else
    require CLASSMAP[$className];
});

define('BASE_PATH', substr(_DIR_, 0, -5)); // Finit avec /
define('CORE_PATH', BASE_PATH . 'lib/myLibs/'); // Finit avec /
//define('DEBUG_KEY', 'debuglp_');

require CORE_PATH . 'console/Colors.php';
require CORE_PATH . 'Tools/RemovesFieldProtection.php';

// Will be the future translation feature
function t(string $texte) : string { return $texte; }
