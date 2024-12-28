<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use Exception;

class TestUseNativeClass
{
  public static function run(): void
  {
    $test = new Exception();
  }
}
