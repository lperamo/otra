<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use examples\deployment\fixFiles\input\vendor\{Test2, Test3};
class TestInlineTwoUse
{
  public static function run(): void
  {
    Test2::noop();
    Test3::noop();
  }
}
