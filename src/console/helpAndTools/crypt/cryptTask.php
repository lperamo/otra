<?php
declare(strict_types=1);
if (true === isset($argv[3]))
  define($_SERVER['OTRA_HASH'], $argv[3]);
else
  require BASE_PATH . 'config/AllConfig.php';

echo crypt($argv[2], $_SERVER['OTRA_HASH']), PHP_EOL;
