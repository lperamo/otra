<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\hash;
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

const HASH_ARG_BLOWFISH_ROUNDS = 2;
$argv[HASH_ARG_BLOWFISH_ROUNDS] = $argv[HASH_ARG_BLOWFISH_ROUNDS] ?? 7;
$saltChars = array_merge(range('A','Z'), range('a','z'), range(0,9));
echo '$2y$0', $argv[HASH_ARG_BLOWFISH_ROUNDS], '$', str_repeat((string)$saltChars[array_rand($saltChars)], 22), PHP_EOL;
