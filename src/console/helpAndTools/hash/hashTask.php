<?php
declare(strict_types=1);
define('HASH_ARG_BLOWFISH_ROUNDS', 2);
$argv[HASH_ARG_BLOWFISH_ROUNDS] = isset($argv[HASH_ARG_BLOWFISH_ROUNDS]) === true ? $argv[HASH_ARG_BLOWFISH_ROUNDS] : 7;
$blowfishSalt = '';
$saltChars = array_merge(range('A','Z'), range('a','z'), range(0,9));

for($index = 0; $index < 22; ++$index)
{
  $blowfishSalt .= $saltChars[array_rand($saltChars)];
}

echo '$2y$0', $argv[HASH_ARG_BLOWFISH_ROUNDS], '$', $blowfishSalt, PHP_EOL;
