<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use examples\deployment\fixFiles\input\vendor\TestStatic;

class TestStaticCall
{
  public static function run(): void
  {
    TestStatic::runTest();
  }
}
