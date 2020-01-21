<?php
define('RELATIVE_PATH_LENGTH', 21); // length of vendor/otra/otra/src/
// Fixes windows awful __DIR__. The path finishes with /
define(
  'BASE_PATH',
  substr( str_replace('\\', '/', __DIR__) . '/', 0, -RELATIVE_PATH_LENGTH)
);

echo '***', BASE_PATH, '***', PHP_EOL;

$binFolderPath = BASE_PATH . 'bin/';

// Creates the folder if needed
if (file_exists($binFolderPath) === false)
  mkdir($binFolderPath);

// Cleans previous files
array_map('unlink', glob($binFolderPath . '*'));

// creates a symbolic link from otra console tool to "bin/otra.php"
symlink(BASE_PATH . 'vendor/otra/otra/otra.php', $binFolderPath . 'otra.php');
