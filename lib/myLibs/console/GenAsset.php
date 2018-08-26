<?php
declare(strict_types=1);

// $argv[1] = CACHE_PATH
// $argv[2] = $route
// $argv[3] = $shaName

define('BASE_PATH', realpath(str_replace('\\', '/', __DIR__) . '/../../..') . '/');  // Fixes windows awful __DIR__. The path finishes with /
define('CORE_PATH', BASE_PATH . 'lib/myLibs/');
define('XMODE', 'dev');

require BASE_PATH . 'config/AllConfig.php';
require CORE_PATH . 'Router.php';

// Loads the class mapping
require BASE_PATH . 'cache/php/ClassMap.php';
spl_autoload_register(function(string $className) { require CLASSMAP[$className]; });

// NEEDED ONLY FOR the 'template', function needed because this function is not part of main controllers
// Otherwise the templates cannot execute this translation function.
function t(string $text) : string { return $text; }

// We don't allow errors shown on production !
$oldErrorReporting = error_reporting();
error_reporting(0);
ob_start();

// We launch the route
\lib\myLibs\Router::get($argv[2]);
$content = ob_get_clean();

// We restore the error reporting
error_reporting($oldErrorReporting);

// We generate the file and gzip it
$pathAndFile = $argv[1] . 'tpl/' . $argv[3];
file_put_contents($pathAndFile, preg_replace('@\s{2,}@', ' ', $content));
exec('gzip -f -9 "' . $pathAndFile . '"');
