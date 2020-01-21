<?php
// Fixes windows awful __DIR__. The path finishes with /
define('RELATIVE_PATH_LENGTH', 21);
define(
  'BASE_PATH',
  substr( str_replace('\\', '/', __DIR__) . '/', 0, -RELATIVE_PATH_LENGTH)
);

$binFolderPath = BASE_PATH . 'bin/';

// Creates the folder if needed
if (file_exists($binFolderPath) === false)
  mkdir($binFolderPath);

// Cleans previous files
array_map('unlink', glob($binFolderPath . '*'));

// creates a symbolic link from otra console tool to "bin/otra.php"
symlink(BASE_PATH . 'vendor/otra/otra/otra.php', $binFolderPath . 'otra.php');
