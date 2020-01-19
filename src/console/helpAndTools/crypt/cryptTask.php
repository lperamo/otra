<?php
if (true === isset($argv[3]))
  define(FWK_HASH, $argv[3]);
else
  require BASE_PATH . 'config/AllConfig.php';

echo crypt($argv[2], FWK_HASH), PHP_EOL;
