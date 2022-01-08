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

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function crypt(array $argumentsVector): void
{
  define(__NAMESPACE__ . '\\CRYPT_ITERATIONS', $argumentsVector[CRYPT_ARG_ITERATIONS] ?? 20000);

  if (!is_numeric(CRYPT_ITERATIONS))
  {
    echo 'Iterations parameter must be numeric!', PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  $securitySalt = openssl_random_pseudo_bytes(16);

  echo CLI_INFO_HIGHLIGHT, 'salt (hexadecimal version) : ', END_COLOR, bin2hex($securitySalt), PHP_EOL,
  CLI_INFO_HIGHLIGHT, 'password                   : ', END_COLOR,  hash_pbkdf2(
    'sha256',
    $argumentsVector[CRYPT_ARG_PASSWORD],
    $securitySalt,
    (int)CRYPT_ITERATIONS,
    20
  ), PHP_EOL;
}
