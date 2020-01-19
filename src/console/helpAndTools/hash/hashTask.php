<?php
$argv[2] = isset($argv[2]) === true ? $argv[2] : 7;
$salt = '';
$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));

for($i = 0; $i < 22; ++$i)
{
  $salt .= $salt_chars[array_rand($salt_chars)];
}

echo '$2y$0', $argv[2], '$', $salt, PHP_EOL;
