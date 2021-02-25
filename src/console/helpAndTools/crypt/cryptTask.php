<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

define('CRYPT_ARG_PASSWORD', 2);
define('CRYPT_ARG_ITERATIONS', 3);

$salt = openssl_random_pseudo_bytes(16);

echo CLI_LIGHT_CYAN, 'salt (hexadecimal version) : ', END_COLOR, bin2hex($salt), PHP_EOL,
  CLI_LIGHT_CYAN, 'password                   : ', END_COLOR,  hash_pbkdf2(
    'sha256',
    $argv[CRYPT_ARG_PASSWORD],
    $salt,
    $argv[CRYPT_ARG_ITERATIONS] ?? 20000,
    20
  ), PHP_EOL;
