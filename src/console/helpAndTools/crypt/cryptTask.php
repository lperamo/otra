<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\crypt;

use otra\OtraException;
use const otra\console\{CLI_INFO_HIGHLIGHT,END_COLOR};

const
  CRYPT_ARG_PASSWORD = 2,
  CRYPT_ARG_ITERATIONS = 3;

if (!is_numeric($argv[CRYPT_ARG_ITERATIONS]))
{
  echo 'Iterations parameter must be numeric!';
  throw new OtraException('', 1, '', NULL, [], true);
}

$securitySalt = openssl_random_pseudo_bytes(16);

echo CLI_INFO_HIGHLIGHT, 'salt (hexadecimal version) : ', END_COLOR, bin2hex($securitySalt), PHP_EOL,
  CLI_INFO_HIGHLIGHT, 'password                   : ', END_COLOR,  hash_pbkdf2(
    'sha256',
    $argv[CRYPT_ARG_PASSWORD],
    $securitySalt,
    $argv[CRYPT_ARG_ITERATIONS] ?? 20000,
    20
  ), PHP_EOL;
