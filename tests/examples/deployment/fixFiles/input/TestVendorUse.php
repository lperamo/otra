<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use examples\deployment\fixFiles\input\vendor\Test2;

class TestVendorUse
{
  public static function run(): void
  {
    $test = new Test2();
  }
}
