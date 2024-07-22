<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input\vendor
{
  const
    TEST = 'test',
    TEST3 = ('test3'),
    TEST4 = 'test4' . 'test4; bis',
    TEST5 = TEST,
    TEST6 = ['a' => 1, 2 => 't'],
    TEST7 = 20 + 4 * 8 / 7 ^ 9 && 1 || 2 & 3 | 6;
//  TEST8 = TEST4 . TEST4;
  const TEST = ('test');
  echo 'test,.;super';
  const TEST2 = 'test2';
  echo TEST;
}
