<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\hash;
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

const HASH_ARG_BLOWFISH_ROUNDS = 2;

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @return void
 */
function hash(array $argumentsVector) : void
{
  $argumentsVector[HASH_ARG_BLOWFISH_ROUNDS] ??= 7;
  $saltChars = array_merge(range('A','Z'), range('a','z'), range(0,9));
  echo '$2y$0', $argumentsVector[HASH_ARG_BLOWFISH_ROUNDS], '$', str_repeat((string)$saltChars[array_rand($saltChars)], 22),
  PHP_EOL;
}
